<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @author louis <louis@systemli.org>
 */
class UserProvider implements UserProviderInterface
{
    /**
     * @var string
     */
    private $defaultDomain;
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * Constructor.
     *
     * @param ObjectManager $manager
     * @param string        $defaultDomain
     */
    public function __construct(ObjectManager $manager, $defaultDomain)
    {
        $this->manager = $manager;
        $this->defaultDomain = $defaultDomain;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        if (false === strpos($username, '@')) {
            $username = sprintf('%s@%s', $username, $this->defaultDomain);
        }

        $user = $this->manager->getRepository('App:User')->findByEmail($username);

        if (!$user) {
            throw new UsernameNotFoundException(sprintf('No user with name "%s" was found.', $username));
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Expected an instance of App\Entity\User, but got "%s".', get_class($user))
            );
        }

        if (!$this->supportsClass(get_class($user))) {
            throw new UnsupportedUserException(
                sprintf('Expected an instance of %s, but got "%s".', User::class, get_class($user))
            );
        }

        if (null === $reloadedUser = $this->manager->getRepository('App:User')->findOneBy(array('id' => $user->getId()))) {
            throw new UsernameNotFoundException(sprintf('User with ID "%d" could not be reloaded.', $user->getId()));
        }

        return $reloadedUser;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        $userClass = User::class;

        return $userClass === $class || is_subclass_of($class, $userClass);
    }
}
