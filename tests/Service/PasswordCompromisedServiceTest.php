<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Enum\UserNotificationType;
use App\Event\UserNotificationEvent;
use App\Service\PasswordCompromisedService;
use App\Service\UserNotificationRateLimiter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PasswordCompromisedServiceTest extends TestCase
{
    private UserNotificationRateLimiter|MockObject $rateLimiter;
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private ValidatorInterface|MockObject $validator;
    private PasswordCompromisedService $service;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->rateLimiter = $this->createMock(UserNotificationRateLimiter::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->service = new PasswordCompromisedService(
            $this->rateLimiter,
            $this->eventDispatcher,
            $this->validator,
            $this->logger
        );
    }

    public function testCheckAndNotifyWhenRateLimitNotAllowed(): void
    {
        $user = new User('test@example.org');
        $password = 'compromised_password';
        $locale = 'en';

        // Rate limiter denies the notification
        $this->rateLimiter
            ->expects($this->once())
            ->method('isAllowed')
            ->with($user, UserNotificationType::PASSWORD_COMPROMISED)
            ->willReturn(false);

        // Validator should not be called if rate limit is not allowed
        $this->validator->expects($this->never())->method('validate');

        // Rate limiter save should not be called
        $this->rateLimiter->expects($this->never())->method('save');

        // Event dispatcher should not be called
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->service->checkAndNotify($user, $password, $locale);
    }

    public function testCheckAndNotifyWhenPasswordNotCompromised(): void
    {
        $user = new User('test@example.org');
        $password = 'secure_password';
        $locale = 'de';

        // Rate limiter allows the notification
        $this->rateLimiter
            ->expects($this->once())
            ->method('isAllowed')
            ->with($user, UserNotificationType::PASSWORD_COMPROMISED)
            ->willReturn(true);

        // Validator returns no violations (password is not compromised)
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->expects($this->once())->method('count')->willReturn(0);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($password, $this->callback(static function ($constraint) {
                return $constraint instanceof NotCompromisedPassword;
            }))
            ->willReturn($violations);

        // Rate limiter save should not be called for non-compromised passwords
        $this->rateLimiter->expects($this->never())->method('save');

        // Event dispatcher should not be called for non-compromised passwords
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->service->checkAndNotify($user, $password, $locale);
    }

    public function testCheckAndNotifyWhenPasswordIsCompromised(): void
    {
        $user = new User('test@example.org');
        $password = 'compromised_password';
        $locale = 'fr';

        // Rate limiter allows the notification
        $this->rateLimiter
            ->expects($this->once())
            ->method('isAllowed')
            ->with($user, UserNotificationType::PASSWORD_COMPROMISED)
            ->willReturn(true);

        // Validator returns violations (password is compromised)
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->expects($this->once())->method('count')->willReturn(1);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($password, $this->callback(static function ($constraint) {
                return $constraint instanceof NotCompromisedPassword;
            }))
            ->willReturn($violations);

        // Rate limiter save should be called
        $this->rateLimiter
            ->expects($this->once())
            ->method('save')
            ->with($user, UserNotificationType::PASSWORD_COMPROMISED);

        // Event dispatcher should be called with correct event
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(static function ($event) use ($user, $locale) {
                    return $event instanceof UserNotificationEvent
                           && $event->getUser() === $user
                           && $event->getNotificationType() === UserNotificationType::PASSWORD_COMPROMISED
                           && $event->getLocale() === $locale;
                }),
                UserNotificationEvent::COMPROMISED_PASSWORD
            );

        $this->service->checkAndNotify($user, $password, $locale);
    }
}
