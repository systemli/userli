<?php

namespace App\Event;

use App\Entity\User;
use App\Traits\LocaleTrait;
use App\Traits\UserAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class UserCreatedEvent extends Event
{
    use UserAwareTrait;
    use LocaleTrait;

    public function __construct(
        private readonly User $user,
        private readonly string $locale
    )
    {}
}
