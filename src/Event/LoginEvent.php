<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class LoginEvent extends Event
{
    public const NAME = 'user.login';

    public function __construct(private readonly User $user, private readonly string $plainPassword)
    {
    }

    public function getPlainPassword(): string
    {
        return $this->plainPassword;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
