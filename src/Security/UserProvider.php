<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class UserProvider.
 */
class UserProvider implements UserProviderInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * UserProvider constructor.
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        if (false === strpos($username, '@')) {
            $defaultDomain = $this->manager->getRepository('App:Domain')->getDefaultDomain();
            $username = sprintf('%s@%s', $username, $defaultDomain);
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
            throw new UnsupportedUserException(sprintf('Expected an instance of App\Entity\User, but got "%s".', get_class($user)));
        }

        if (null === $reloadedUser = $this->manager->getRepository('App:User')->findOneBy(['id' => $user->getId()])) {
            throw new UsernameNotFoundException(sprintf('User with ID "%d" could not be reloaded.', $user->getId()));
        }

        return $reloadedUser;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class): bool
    {
        $userClass = User::class;

        return $userClass === $class || is_subclass_of($class, $userClass);
    }
}
