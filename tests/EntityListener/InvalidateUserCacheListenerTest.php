<?php

declare(strict_types=1);

namespace App\Tests\EntityListener;

use App\Entity\User;
use App\EntityListener\InvalidateUserCacheListener;
use App\Message\InvalidateUserCache;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class InvalidateUserCacheListenerTest extends TestCase
{
    public function testPostPersist(): void
    {
        $email = 'user@example.org';
        $user = new User($email);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('dispatch')
            ->with($this->callback(static function (InvalidateUserCache $message) use ($email): bool {
                return $message->email === $email;
            }))
            ->willReturn(new Envelope(new InvalidateUserCache($email)));

        $listener = new InvalidateUserCacheListener($bus);
        $listener->invalidate($user);
    }

    public function testPostUpdate(): void
    {
        $email = 'user@example.org';
        $user = new User($email);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('dispatch')
            ->with($this->callback(static function (InvalidateUserCache $message) use ($email): bool {
                return $message->email === $email;
            }))
            ->willReturn(new Envelope(new InvalidateUserCache($email)));

        $listener = new InvalidateUserCacheListener($bus);
        $listener->invalidate($user);
    }
}
