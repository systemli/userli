<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\User;
use App\Traits\PlainPasswordTrait;
use App\Traits\UserAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

final class LoginEvent extends Event
{
    use PlainPasswordTrait;
    use UserAwareTrait;

    public const NAME = 'user.login';

    public function __construct(User $user, string $plainPassword)
    {
        $this->user = $user;
        $this->plainPassword = $plainPassword;
    }
}
