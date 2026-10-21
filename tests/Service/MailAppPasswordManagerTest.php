<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\MailAppPassword;
use App\Entity\User;
use App\Exception\DecryptionFailedException;
use App\Handler\MailCryptKeyHandler;
use App\Repository\MailAppPasswordRepository;
use App\Service\MailAppPasswordManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class MailAppPasswordManagerTest extends TestCase
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
            ->willReturnCallback(static function (MailAppPassword $mailAppPassword) use (&$persisted): void {
                $persisted = $mailAppPassword;
            });
        $entityManager->expects($this->once())->method('flush');

        $manager = new MailAppPasswordManager($this->createStub(MailAppPasswordRepository::class), $entityManager, $this->mailCryptKeyHandler);
        $plain = $manager->create($user, 'Thunderbird', 'password');

        self::assertMatchesRegularExpression('/^[abcdefgijkmnopqrstwxyzABCDEFGHJKLMNPQRSTWXYZ23456789]{4}(-[abcdefgijkmnopqrstwxyzABCDEFGHJKLMNPQRSTWXYZ23456789]{4}){5}$/', $plain);
        self::assertInstanceOf(MailAppPassword::class, $persisted);
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
            ->willReturnCallback(static function (MailAppPassword $mailAppPassword) use (&$persisted): void {
                $persisted = $mailAppPassword;
            });

        $manager = new MailAppPasswordManager($this->createStub(MailAppPasswordRepository::class), $entityManager, $this->mailCryptKeyHandler);
        $plain = $manager->create($user, 'Phone', 'password');

        self::assertTrue($user->hasMailCryptSecretBox());
        self::assertFalse($user->getMailCryptEnabled());
        self::assertInstanceOf(MailAppPassword::class, $persisted);
        self::assertSame(
            $this->mailCryptKeyHandler->decrypt($user, 'password'),
            $manager->decryptMailCryptKey($persisted, $plain)
        );
    }

    public function testDecryptMailCryptKeyWithWrongPasswordThrows(): void
    {
        $user = new User('user@example.org');
        $manager = new MailAppPasswordManager($this->createStub(MailAppPasswordRepository::class), $this->createStub(EntityManagerInterface::class), $this->mailCryptKeyHandler);
        $manager->create($user, 'Phone', 'password');

        $mailAppPassword = new MailAppPassword($user, 'Phone', hash('sha256', 'secret'), (string) $user->getMailCryptSecretBox());

        $this->expectException(DecryptionFailedException::class);
        $this->expectExceptionMessage(MailCryptKeyHandler::MESSAGE_DECRYPTION_FAILED);
        $manager->decryptMailCryptKey($mailAppPassword, 'wrong');
    }

    public function testFindLooksUpByUserAndHash(): void
    {
        $user = new User('user@example.org');
        $mailAppPassword = new MailAppPassword($user, 'Phone', hash('sha256', 'secret'), 'box');

        $repository = $this->createMock(MailAppPasswordRepository::class);
        $repository->expects($this->exactly(2))->method('findOneBy')
            ->willReturnCallback(static fn (array $criteria) => $criteria === ['user' => $user, 'hash' => hash('sha256', 'secret')] ? $mailAppPassword : null);

        $manager = new MailAppPasswordManager($repository, $this->createStub(EntityManagerInterface::class), $this->mailCryptKeyHandler);

        self::assertSame($mailAppPassword, $manager->find($user, 'secret'));
        self::assertNull($manager->find($user, 'wrong'));
    }
}
