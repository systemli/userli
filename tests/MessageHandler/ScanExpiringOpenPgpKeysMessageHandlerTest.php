<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\OpenPgpKey;
use App\Entity\User;
use App\Message\ScanExpiringOpenPgpKeys;
use App\MessageHandler\ScanExpiringOpenPgpKeysHandler;
use App\Repository\OpenPgpKeyRepository;
use App\Repository\UserNotificationRepository;
use App\Service\OpenPgpKeyManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class ScanExpiringOpenPgpKeysMessageHandlerTest extends TestCase
{
    public function testInvokeWithNoExpiringKeys(): void
    {
        $logged = [];
        $handler = $this->createHandler([], [], [], $logged);

        ($handler)(new ScanExpiringOpenPgpKeys());

        self::assertSame([['count' => 0], ['dispatched' => 0]], $logged);
    }

    public function testInvokeDispatchesNotificationPerUserWithMatchingEmail(): void
    {
        $alice = new User('alice@example.org');
        $bob = new User('bob@example.org');

        $logged = [];
        $dispatched = [];
        $handler = $this->createHandler(
            [$this->createKey('alice@example.org')],
            [$alice, $bob],
            [],
            $logged,
            $dispatched
        );

        ($handler)(new ScanExpiringOpenPgpKeys());

        self::assertCount(1, $dispatched);
        self::assertSame($alice, $dispatched[0]->user);
        self::assertSame([['count' => 1], ['dispatched' => 1]], $logged);
    }

    public function testInvokeSkipsEmailsWithExistingExpiryNotification(): void
    {
        $logged = [];
        $handler = $this->createHandler(
            [$this->createKey('alice@example.org')],
            [new User('alice@example.org')],
            ['alice@example.org'],
            $logged
        );

        ($handler)(new ScanExpiringOpenPgpKeys());

        self::assertSame([['count' => 1], ['dispatched' => 0]], $logged);
    }

    public function testInvokeWithExpiringKeysButNoMatchingUsers(): void
    {
        $logged = [];
        $handler = $this->createHandler([$this->createKey('alice@example.org')], [], [], $logged);

        ($handler)(new ScanExpiringOpenPgpKeys());

        self::assertSame([['count' => 1], ['dispatched' => 0]], $logged);
    }

    private function createKey(string $email): OpenPgpKey
    {
        $key = new OpenPgpKey();
        $key->setEmail($email);
        $key->setExpiresAt(new DateTime());

        return $key;
    }

    /**
     * @param array<int, OpenPgpKey> $expiringKeys
     * @param array<int, User>       $users
     * @param array<int, string>     $alreadyNotifiedEmails
     * @param array<int, array>      &$logged
     * @param array<int, object>     &$dispatched
     */
    private function createHandler(
        array $expiringKeys,
        array $users,
        array $alreadyNotifiedEmails,
        array &$logged,
        array &$dispatched = []
    ): ScanExpiringOpenPgpKeysHandler {
        $keyRepo = $this->createStub(OpenPgpKeyRepository::class);
        $keyRepo->method('findKeysByExpiry')->willReturn($expiringKeys);

        $notificationRepo = $this->createStub(UserNotificationRepository::class);
        $notificationRepo->method('emailHasExpiryNotification')
            ->willReturnCallback(fn (string $email): bool => in_array($email, $alreadyNotifiedEmails, true));

        $keyManager = $this->createStub(OpenPgpKeyManager::class);
        $keyManager->method('findUsersByEmail')->willReturnCallback(
            fn (string $email): array => array_values(
                array_filter($users, fn (User $user): bool => $user->getEmail() === $email)
            )
        );

        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('info')->willReturnCallback(function (string $message, array $context = []) use (&$logged): void {
            $logged[] = $context;
        });

        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(
            function (object $message) use (&$dispatched): Envelope {
                $dispatched[] = $message;

                return new Envelope($message);
            }
        );

        return new ScanExpiringOpenPgpKeysHandler(
            $this->createStub(EntityManagerInterface::class),
            $bus,
            $logger,
            $keyManager,
            $keyRepo,
            $notificationRepo
        );
    }
}
