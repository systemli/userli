<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Domain;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if (!str_contains($identifier, '@')) {
            $defaultDomain = $this->manager->getRepository(Domain::class)->getDefaultDomain();
            $identifier = sprintf('%s@%s', $identifier, $defaultDomain);
        }

        $user = $this->manager->getRepository(User::class)->findByEmail($identifier);

        if (!$user) {
            throw new UserNotFoundException(sprintf('No user with name "%s" was found.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        $reloadedUser = $this->manager->getRepository(User::class)->findOneBy(['id' => $user->getId(), 'deleted' => false]);
        if (null === $reloadedUser) {
            throw new UserNotFoundException(sprintf('User with ID "%d" could not be reloaded.', $user->getId()));
        }

        return $reloadedUser;
    }

    public function supportsClass($class): bool
    {
        $userClass = User::class;

        return $userClass === $class || is_subclass_of($class, $userClass);
    }
}
