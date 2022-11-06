<?php

namespace App\Security;

use App\Entity\Domain;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class UserProvider.
 */
class UserProvider implements UserProviderInterface
{
    private EntityManagerInterface $manager;

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
    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if (false === strpos($identifier, '@')) {
            $defaultDomain = $this->manager->getRepository(Domain::class)->getDefaultDomain();
            $identifier = sprintf('%s@%s', $identifier, $defaultDomain);
        }

        $user = $this->manager->getRepository(User::class)->findByEmail($identifier);

        if (!$user) {
            throw new UserNotFoundException(sprintf('No user with name "%s" was found.', $identifier));
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Expected an instance of App\Entity\User, but got "%s".', get_class($user)));
        }

        if (null === $reloadedUser = $this->manager->getRepository(User::class)->findOneBy(['id' => $user->getId()])) {
            throw new UserNotFoundException(sprintf('User with ID "%d" could not be reloaded.', $user->getId()));
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
