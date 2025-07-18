<?php

namespace App\Event;

use App\Entity\User;
use App\Traits\PlainPasswordTrait;
use App\Traits\UserAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class LoginEvent extends Event
{
    use UserAwareTrait;
    use PlainPasswordTrait;

    public const NAME = 'user.login';



    public function __construct(User $user, string $plainPassword)
    {
        $this->user = $user;
        $this->plainPassword = $plainPassword;
    }
}
