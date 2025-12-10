<?php

declare(strict_types=1);

namespace App\Handler;

use App\Entity\User;
use App\Event\LoginEvent;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class UserAuthenticationHandler
{
    public function __construct(
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        protected EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function authenticate(User $user, string $password): ?User
    {
        $hasher = $this->passwordHasherFactory->getPasswordHasher($user);
        if (!$hasher->verify($user->getPassword(), $password)) {
            return null;
        }

        $this->eventDispatcher->dispatch(
            new LoginEvent($user, $password),
            LoginEvent::NAME
        );

        return $user;
    }
}
