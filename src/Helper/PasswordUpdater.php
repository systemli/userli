<?php

namespace App\Helper;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

readonly class PasswordUpdater
{
    public function __construct(private PasswordHasherFactoryInterface $passwordHasherFactory)
    {
    }

    public function updatePassword(User $user, string $plainPassword): void
    {
        $user->setPassword($this->passwordHasherFactory->getPasswordHasher($user)->hash($plainPassword));
        $user->updateUpdatedTime();
    }
}
