<?php

declare(strict_types=1);

namespace App\Tests\EntityListener;

use App\Entity\User;
use App\EntityListener\InvalidateUserCacheListener;
use App\Message\InvalidateUserCache;
use Doctrine\ORM\Event\PostPersistEventArgs;
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

        $args = $this->createStub(PostPersistEventArgs::class);

        $listener = new InvalidateUserCacheListener($bus);
        $listener->postPersist($user, $args);
    }
}
