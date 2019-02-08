<?php

namespace App\Voter;

use App\Entity\Alias;
use App\Entity\User;
use App\Enum\Roles;
use App\Guesser\DomainGuesser;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

/**
 * @author tim <tim@systemli.org>
 */
class DomainVoter extends Voter
{
    /**
     * @var Security
     */
    private $security;
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * DomainVoter constructor.
     *
     * @param Security      $security
     * @param ObjectManager $manager
     */
    public function __construct(Security $security, ObjectManager $manager)
    {
        $this->security = $security;
        $this->manager = $manager;
    }

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed  $subject   The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports($attribute, $subject)
    {
        // only vote on User and Alias objects inside this voter
        if ($subject instanceof User || $subject instanceof Alias) {
            if (in_array($attribute, [
                'ROLE_USRMGMT_ADMIN_USER_LIST',
                'ROLE_USRMGMT_ADMIN_USER_VIEW',
                'ROLE_USRMGMT_ADMIN_USER_CREATE',
                'ROLE_USRMGMT_ADMIN_USER_EDIT',
                'ROLE_USRMGMT_ADMIN_USER_DELETE',
                'ROLE_USRMGMT_ADMIN_ALIAS_LIST',
                'ROLE_USRMGMT_ADMIN_ALIAS_VIEW',
                'ROLE_USRMGMT_ADMIN_ALIAS_CREATE',
                'ROLE_USRMGMT_ADMIN_ALIAS_EDIT',
                'ROLE_USRMGMT_ADMIN_ALIAS_DELETE',
            ])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param string         $attribute
     * @param mixed          $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        // normal admins can do everything
        if ($this->security->isGranted(Roles::ADMIN)) {
            return true;
        }

        // must be at least domain admin
        if (!$this->security->isGranted(Roles::DOMAIN_ADMIN)) {
            return false;
        }

        // nobody but Admins is allowed to create/edit admins
        if ($subject instanceof User) {
            if ($subject->hasRole(Roles::ADMIN)) {
                return false;
            }
        }

        $user = $this->manager->getRepository('App:User')
            ->findByEmail($this->security->getUser()->getUsername());
        $userDomain = $user->getDomain();

        if ((in_array($attribute, [
            'ROLE_USRMGMT_ADMIN_USER_LIST',
            'ROLE_USRMGMT_ADMIN_USER_VIEW',
            'ROLE_USRMGMT_ADMIN_USER_DELETE',
            'ROLE_USRMGMT_ADMIN_ALIAS_LIST',
            'ROLE_USRMGMT_ADMIN_ALIAS_VIEW',
            'ROLE_USRMGMT_ADMIN_ALIAS_DELETE',
        ])) && ($userDomain === $subject->getDomain())) {
            // domain admin can only see own domain
            return true;
        }

        $guesser = new DomainGuesser($this->manager);

        if ((in_array($attribute, [
            'ROLE_USRMGMT_ADMIN_USER_CREATE',
            'ROLE_USRMGMT_ADMIN_USER_EDIT',
            ])) && ($userDomain === $guesser->guess($subject->getEmail()))) {
            return true;
        }

        if ((in_array($attribute, [
                'ROLE_USRMGMT_ADMIN_ALIAS_CREATE',
                'ROLE_USRMGMT_ADMIN_ALIAS_EDIT',
            ])) && ($userDomain === $guesser->guess($subject->getSource()))) {
            return true;
        }
    }
}
