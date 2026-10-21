<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\AppPassword;
use App\Entity\User;
use App\Exception\DecryptionFailedException;
use App\Handler\MailCryptKeyHandler;
use App\Repository\AppPasswordRepository;
use App\Service\AppPasswordManager;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class AppPasswordManagerTest extends TestCase
{
    private MailCryptKeyHandler $mailCryptKeyHandler;

    protected function setUp(): void
    {
        $this->mailCryptKeyHandler = new MailCryptKeyHandler($this->createStub(EntityManagerInterface::class));
    }

    public function testCreateStoresHashAndEncryptedMailCryptKey(): void
    {
        $user = new User('user@example.org');
        $this->mailCryptKeyHandler->create($user, 'password');
        $user->eraseCredentials();
        $privateKey = $this->mailCryptKeyHandler->decrypt($user, 'password');

        $persisted = null;
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')
            ->willReturnCallback(static function (AppPassword $appPassword) use (&$persisted): void {
                $persisted = $appPassword;
            });
        $entityManager->expects($this->once())->method('flush');

        $manager = new AppPasswordManager($this->createStub(AppPasswordRepository::class), $entityManager, $this->mailCryptKeyHandler);
        $plain = $manager->create($user, 'Thunderbird', 'password');

        self::assertMatchesRegularExpression('/^[abcdefgijkmnopqrstwxyzABCDEFGHJKLMNPQRSTWXYZ23456789]{4}(-[abcdefgijkmnopqrstwxyzABCDEFGHJKLMNPQRSTWXYZ23456789]{4}){5}$/', $plain);
        self::assertInstanceOf(AppPassword::class, $persisted);
        self::assertSame($user, $persisted->getUser());
        self::assertSame('Thunderbird', $persisted->getName());
        self::assertSame(hash('sha256', $plain), $persisted->getHash());
        self::assertNotNull($persisted->getCreationTime());
        self::assertSame($privateKey, $manager->decryptMailCryptKey($persisted, $plain));
        self::assertNull($user->getPlainMailCryptPrivateKey());
    }

    public function testCreateCreatesMailCryptKeyIfMissing(): void
    {
        $user = new User('user@example.org');

        $persisted = null;
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('persist')
            ->willReturnCallback(static function (AppPassword $appPassword) use (&$persisted): void {
                $persisted = $appPassword;
            });

        $manager = new AppPasswordManager($this->createStub(AppPasswordRepository::class), $entityManager, $this->mailCryptKeyHandler);
        $plain = $manager->create($user, 'Phone', 'password');

        self::assertTrue($user->hasMailCryptSecretBox());
        self::assertFalse($user->getMailCryptEnabled());
        self::assertInstanceOf(AppPassword::class, $persisted);
        self::assertSame(
            $this->mailCryptKeyHandler->decrypt($user, 'password'),
            $manager->decryptMailCryptKey($persisted, $plain)
        );
    }

    public function testDecryptMailCryptKeyWithWrongPasswordThrows(): void
    {
        $user = new User('user@example.org');
        $manager = new AppPasswordManager($this->createStub(AppPasswordRepository::class), $this->createStub(EntityManagerInterface::class), $this->mailCryptKeyHandler);
        $manager->create($user, 'Phone', 'password');

        $appPassword = new AppPassword($user, 'Phone', hash('sha256', 'secret'), (string) $user->getMailCryptSecretBox());

        $this->expectException(DecryptionFailedException::class);
        $this->expectExceptionMessage(MailCryptKeyHandler::MESSAGE_DECRYPTION_FAILED);
        $manager->decryptMailCryptKey($appPassword, 'wrong');
    }

    public function testFindLooksUpByUserAndHash(): void
    {
        $user = new User('user@example.org');
        $appPassword = new AppPassword($user, 'Phone', hash('sha256', 'secret'), 'box');

        $repository = $this->createMock(AppPasswordRepository::class);
        $repository->expects($this->exactly(2))->method('findValidByHash')
            ->willReturnCallback(static fn (User $u, string $hash) => $u === $user && hash('sha256', 'secret') === $hash ? $appPassword : null);

        $manager = new AppPasswordManager($repository, $this->createStub(EntityManagerInterface::class), $this->mailCryptKeyHandler);

        self::assertSame($appPassword, $manager->find($user, 'secret'));
        self::assertNull($manager->find($user, 'wrong'));
    }

    public function testCreateWebmailCredentialExpires(): void
    {
        $user = new User('user@example.org');

        $persisted = null;
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('persist')
            ->willReturnCallback(static function (AppPassword $appPassword) use (&$persisted): void {
                $persisted = $appPassword;
            });

        $manager = new AppPasswordManager($this->createStub(AppPasswordRepository::class), $entityManager, $this->mailCryptKeyHandler);
        [$plain, $expiresAt] = $manager->createWebmailCredential($user, 'password');

        self::assertInstanceOf(AppPassword::class, $persisted);
        self::assertSame(hash('sha256', $plain), $persisted->getHash());
        self::assertStringStartsWith('Webmail ', $persisted->getName());
        self::assertSame($expiresAt, $persisted->getExpiresAt());
        self::assertEqualsWithDelta(new DateTimeImmutable('+12 hours')->getTimestamp(), $expiresAt->getTimestamp(), 5);
    }
}
