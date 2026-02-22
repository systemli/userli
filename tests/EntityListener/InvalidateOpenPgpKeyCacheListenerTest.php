<?php

declare(strict_types=1);

namespace App\Tests\EntityListener;

use App\Entity\OpenPgpKey;
use App\EntityListener\InvalidateOpenPgpKeyCacheListener;
use App\Message\InvalidateOpenPgpKeyCache;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class InvalidateOpenPgpKeyCacheListenerTest extends TestCase
{
    public function testPostPersist(): void
    {
        $email = 'alice@example.org';
        $openPgpKey = new OpenPgpKey();
        $openPgpKey->setEmail($email);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('dispatch')
            ->with($this->callback(static function (InvalidateOpenPgpKeyCache $message) use ($email): bool {
                return $message->email === $email;
            }))
            ->willReturn(new Envelope(new InvalidateOpenPgpKeyCache($email)));

        $listener = new InvalidateOpenPgpKeyCacheListener($bus);
        $listener->postPersist($openPgpKey);
    }

    public function testPostUpdate(): void
    {
        $email = 'alice@example.org';
        $openPgpKey = new OpenPgpKey();
        $openPgpKey->setEmail($email);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('dispatch')
            ->with($this->callback(static function (InvalidateOpenPgpKeyCache $message) use ($email): bool {
                return $message->email === $email;
            }))
            ->willReturn(new Envelope(new InvalidateOpenPgpKeyCache($email)));

        $listener = new InvalidateOpenPgpKeyCacheListener($bus);
        $listener->postUpdate($openPgpKey);
    }

    public function testPostRemove(): void
    {
        $email = 'alice@example.org';
        $openPgpKey = new OpenPgpKey();
        $openPgpKey->setEmail($email);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('dispatch')
            ->with($this->callback(static function (InvalidateOpenPgpKeyCache $message) use ($email): bool {
                return $message->email === $email;
            }))
            ->willReturn(new Envelope(new InvalidateOpenPgpKeyCache($email)));

        $listener = new InvalidateOpenPgpKeyCacheListener($bus);
        $listener->postRemove($openPgpKey);
    }
}
