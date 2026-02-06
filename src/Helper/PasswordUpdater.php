<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final readonly class PasswordUpdater
{
    public function __construct(private PasswordHasherFactoryInterface $passwordHasherFactory)
    {
    }

    public function updatePassword(User $user, string $plainPassword): void
    {
        $user->setPassword($this->passwordHasherFactory->getPasswordHasher($user)->hash($plainPassword));
    }
}
