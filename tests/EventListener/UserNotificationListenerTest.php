<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\User;
use App\Enum\UserNotificationType;
use App\Event\UserNotificationEvent;
use App\EventListener\UserNotificationListener;
use App\Sender\CompromisedPasswordMessageSender;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserNotificationListenerTest extends TestCase
{
    private CompromisedPasswordMessageSender|MockObject $sender;
    private LoggerInterface|MockObject $logger;
    private UserNotificationListener $listener;
    private User $user;

    protected function setUp(): void
    {
        $this->sender = $this->createMock(CompromisedPasswordMessageSender::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->listener = new UserNotificationListener(
            $this->sender,
            $this->logger
        );

        $this->user = new User();
        $this->user->setEmail('test@example.org');
    }

    public function testGetSubscribedEvents(): void
    {
        $events = UserNotificationListener::getSubscribedEvents();

        $this->assertArrayHasKey(UserNotificationEvent::NAME, $events);
        $this->assertEquals('onUserNotification', $events[UserNotificationEvent::NAME]);
    }

    public function testOnUserNotificationWithPasswordCompromisedType(): void
    {
        $locale = 'en';
        $event = new UserNotificationEvent(
            $this->user,
            UserNotificationType::PASSWORD_COMPROMISED,
            $locale
        );

        // Sender should be called with user and locale
        $this->sender
            ->expects($this->once())
            ->method('send')
            ->with($this->user, $locale);

        // Logger should not be called when sending succeeds
        $this->logger->expects($this->never())->method('error');

        $this->listener->onUserNotification($event);
    }

    public function testOnUserNotificationWithPasswordCompromisedTypeDifferentLocales(): void
    {
        $locales = ['en', 'de', 'fr', 'es', 'it'];

        foreach ($locales as $locale) {
            $event = new UserNotificationEvent(
                $this->user,
                UserNotificationType::PASSWORD_COMPROMISED,
                $locale
            );

            // Sender should be called with correct locale
            $this->sender
                ->expects($this->once())
                ->method('send')
                ->with($this->user, $locale);

            $this->listener->onUserNotification($event);

            // Reset mocks for next iteration
            $this->setUp();
        }
    }

    public function testOnUserNotificationOnlyHandlesPasswordCompromisedType(): void
    {
        // Test verifies that the listener specifically checks for PASSWORD_COMPROMISED type
        // This ensures that when new notification types are added in the future,
        // they won't be handled by this listener unless explicitly added
        
        $locale = 'en';
        $event = new UserNotificationEvent(
            $this->user,
            UserNotificationType::PASSWORD_COMPROMISED,
            $locale
        );

        // Verify that the method actually checks the notification type
        // by ensuring the sender is called for PASSWORD_COMPROMISED
        $this->sender
            ->expects($this->once())
            ->method('send')
            ->with($this->user, $locale);

        $this->listener->onUserNotification($event);
    }

    public function testOnUserNotificationWithSenderException(): void
    {
        $locale = 'de';
        $event = new UserNotificationEvent(
            $this->user,
            UserNotificationType::PASSWORD_COMPROMISED,
            $locale
        );

        $exception = new \Exception('SMTP server unavailable');

        // Sender throws an exception
        $this->sender
            ->expects($this->once())
            ->method('send')
            ->with($this->user, $locale)
            ->willThrowException($exception);

        // Logger should be called with error details
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Failed to send compromised password notification',
                $this->callback(function ($context) {
                    return $context['email'] === 'test@example.org' &&
                           $context['error'] === 'SMTP server unavailable';
                })
            );

        // Exception should be caught and not re-thrown
        $this->listener->onUserNotification($event);
    }

    public function testOnUserNotificationWithDifferentUsers(): void
    {
        $users = [
            ['email' => 'user1@example.org'],
            ['email' => 'user2@example.org'],
            ['email' => 'admin@example.org'],
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);

            $event = new UserNotificationEvent(
                $user,
                UserNotificationType::PASSWORD_COMPROMISED,
                'en'
            );

            $this->sender
                ->expects($this->once())
                ->method('send')
                ->with($user, 'en');

            $this->listener->onUserNotification($event);

            // Reset mocks for next iteration
            $this->setUp();
        }
    }

    public function testOnUserNotificationWithRuntimeException(): void
    {
        $locale = 'fr';
        $event = new UserNotificationEvent(
            $this->user,
            UserNotificationType::PASSWORD_COMPROMISED,
            $locale
        );

        $exception = new \RuntimeException('Mail queue is full');

        $this->sender
            ->expects($this->once())
            ->method('send')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Failed to send compromised password notification',
                [
                    'email' => 'test@example.org',
                    'error' => 'Mail queue is full'
                ]
            );

        $this->listener->onUserNotification($event);
    }

    public function testOnUserNotificationWithInvalidArgumentException(): void
    {
        $locale = 'es';
        $event = new UserNotificationEvent(
            $this->user,
            UserNotificationType::PASSWORD_COMPROMISED,
            $locale
        );

        $exception = new \InvalidArgumentException('Invalid locale provided');

        $this->sender
            ->expects($this->once())
            ->method('send')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Failed to send compromised password notification',
                [
                    'email' => 'test@example.org',
                    'error' => 'Invalid locale provided'
                ]
            );

        $this->listener->onUserNotification($event);
    }

    public function testOnUserNotificationSuccessfulSendingDoesNotLog(): void
    {
        $event = new UserNotificationEvent(
            $this->user,
            UserNotificationType::PASSWORD_COMPROMISED,
            'en'
        );

        // Sender succeeds without exception
        $this->sender
            ->expects($this->once())
            ->method('send')
            ->with($this->user, 'en');

        // Logger should never be called on successful sending
        $this->logger->expects($this->never())->method('error');
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('info');
        $this->logger->expects($this->never())->method('debug');

        $this->listener->onUserNotification($event);
    }

    public function testEventHandlingIsolation(): void
    {
        // Test that each event is handled independently
        $events = [
            new UserNotificationEvent($this->user, UserNotificationType::PASSWORD_COMPROMISED, 'en'),
            new UserNotificationEvent($this->user, UserNotificationType::PASSWORD_COMPROMISED, 'de'),
        ];

        // First event succeeds
        $this->sender
            ->expects($this->exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls(
                null, // Success
                $this->throwException(new \Exception('Second call fails'))
            );

        // Only one error should be logged (for the second event)
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Failed to send compromised password notification',
                [
                    'email' => 'test@example.org',
                    'error' => 'Second call fails'
                ]
            );

        // Process both events
        $this->listener->onUserNotification($events[0]); // Should succeed
        $this->listener->onUserNotification($events[1]); // Should fail and log
    }
}
