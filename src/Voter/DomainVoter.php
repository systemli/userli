<?php

namespace App\Voter;

use App\Entity\Alias;
use App\Entity\User;
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
     * @param Security $security
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
     * @param mixed $subject The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports($attribute, $subject)
    {
        // only vote on User and Alias objects inside this voter
        if ($subject instanceof User || $subject instanceof Alias) {
            return true;
        }

        return false;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        // $subject doesn't have domain on creation
        if (null === $subjectDomain = $subject->getDomain() ) {
            return true;
        }

        // must be at least domain admin
        if (!$this->security->isGranted('ROLE_DOMAIN_ADMIN')) {
            return false;
        }

        // normal admins can see everything
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // we compare with domain of domain admin
        $user = $this->manager->getRepository('App:User')
            ->findByEmail($this->security->getUser()->getUsername());

        if ($user->getDomain() === $subjectDomain ) {
            return true;
        }

        return false;
    }
}