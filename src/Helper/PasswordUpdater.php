<?php

namespace App\Helper;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * Class PasswordUpdater.
 */
class PasswordUpdater
{
    private PasswordHasherFactoryInterface $passwordHasherFactory;

    /**
     * PasswordUpdater constructor.
     */
    public function __construct(PasswordHasherFactoryInterface $passwordHasherFactory)
    {
        $this->passwordHasherFactory = $passwordHasherFactory;
    }

    public function updatePassword(User $user, string $plainPassword = null): void
    {
        if (null === $plainPassword) {
            $plainPassword = $user->getPlainPassword();
        }

        if (!$plainPassword) {
            return;
        }

        $hasher = $this->passwordHasherFactory->getPasswordHasher($user);
        $hash = $hasher->hash($plainPassword);

        $user->setPassword($hash);

        $user->updateUpdatedTime();
    }
}
