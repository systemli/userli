<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Enum\UserNotificationType;
use App\Event\UserNotificationEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class PasswordCompromisedService
{
    private const RATE_LIMIT_HOURS = 24 * 7; // 7 days

    public function __construct(
        private UserNotificationRateLimiter $rateLimiter,
        private EventDispatcherInterface    $eventDispatcher,
        private ValidatorInterface          $validator
    )
    {
    }

    /**
     * Checks if the provided password for the user has been compromised, and notifies the user if necessary.
     *
     * This method applies a rate limit for user notifications to prevent spamming. It validates the given password
     * against a "Not Compromised Password" constraint. If violations are detected, the rate limiter records the
     * event, and a user notification event is dispatched.
     *
     * @param User $user The user for whom the password is being verified.
     * @param string $password The password to check against compromise constraints.
     * @param string $locale The locale used for sending the notification.
     */
    public function checkAndNotify(User $user, string $password, string $locale): void
    {
        if (!$this->rateLimiter->isAllowed($user, UserNotificationType::PASSWORD_COMPROMISED, self::RATE_LIMIT_HOURS)) {
            return;
        }

        $constraint = new NotCompromisedPassword(skipOnError: true);
        $violations = $this->validator->validate($password, $constraint);

        if (count($violations) === 0) {
            return;
        }

        $this->rateLimiter->save($user, UserNotificationType::PASSWORD_COMPROMISED, $locale);
        $this->eventDispatcher->dispatch(new UserNotificationEvent($user, UserNotificationType::PASSWORD_COMPROMISED, $locale), UserNotificationEvent::NAME);
    }
}
