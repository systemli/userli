<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

final class UserEvent extends Event
{
    public const string USER_CREATED = 'user.created';
    public const string USER_DELETED = 'user.deleted';
    public const string USER_RESET = 'user.reset';
    public const string PASSWORD_CHANGED = 'user.password_changed';
    public const string RECOVERY_PROCESS_STARTED = 'recovery_process_started';

    public function __construct(private readonly User $user)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
