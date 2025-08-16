<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\User;
use App\Enum\UserNotificationType;
use Symfony\Contracts\EventDispatcher\Event;

class UserNotificationEvent extends Event
{
    public const NAME = 'user.notification';

    public function __construct(
        private readonly User                 $user,
        private readonly UserNotificationType $notificationType,
        private readonly string               $locale
    )
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getNotificationType(): UserNotificationType
    {
        return $this->notificationType;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
