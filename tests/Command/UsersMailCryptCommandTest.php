<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\UsersMailCryptCommand;
use App\Entity\User;
use App\Handler\MailCryptKeyHandler;
use App\Handler\UserAuthenticationHandler;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class UsersMailCryptCommandTest extends TestCase
{
    private UsersMailCryptCommand $command;
    private Stub&EntityManagerInterface $entityManager;
    private Stub&UserRepository $userRepository;
    private Stub&UserAuthenticationHandler $authenticationHandler;
    private Stub&MailCryptKeyHandler $mailCryptKeyHandler;
    private int $mailCrypt = 1;

    protected function setUp(): void
    {
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->authenticationHandler = $this->createStub(UserAuthenticationHandler::class);
        $this->mailCryptKeyHandler = $this->createStub(MailCryptKeyHandler::class);

        $this->entityManager->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        $this->command = new UsersMailCryptCommand(
            $this->entityManager,
            $this->authenticationHandler,
            $this->mailCryptKeyHandler,
            $this->mailCrypt
        );
    }

    public function testExecuteWithMailCryptArgumentsWhenSet(): void
    {
        $email = 'mailcrypt@example.org';
        $password = 'password';
        $publicKey = 'LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlHYk1CQUdCeXFHU000OUFnRUdCU3VCQkFBakE0R0dBQVFBU09BbXlsNzdGVGdnZ05nZEN0QldBRFdBU2s0WApVckx0RnpWbjBocUZDaExjYUt3Y0VwZmI4RDlPeGFSZHJFM3ZiVmFXd1NNUEZUNHkweVhFdGRqSHZzb0JXVkp5ClVSN25td0t2dEFEWkNmYXpNQW5hS2N3YkpkalBIa0JNT0V1OGpUSnd2bVd1OEV0UHBJU29sdE02K2xmN2N3RTUKNGZaaVIzQ0d2YnRvaVJjVXFsRT0KLS0tLS1FTkQgUFVCTElDIEtFWS0tLS0tCg==';
        $privateKey = 'LS0tLS1CRUdJTiBQUklWQVRFIEtFWS0tLS0tCk1JSHVBZ0VBTUJBR0J5cUdTTTQ5QWdFR0JTdUJCQUFqQklIV01JSFRBZ0VCQkVJQWE0cVIxUGl1ZGZsazgzSDQKN0lXdG5zdE80QjNaQ0tkVWhGTTBBZXpLcUc2KzZPMXR3cklHL2preXYwZm81ZTZQWDBtVUtXSHY2OGJMZ1FKNQo3UUIrYmwyaGdZa0RnWVlBQkFCSTRDYktYdnNWT0NDQTJCMEswRllBTllCS1RoZFNzdTBYTldmU0dvVUtFdHhvCnJCd1NsOXZ3UDA3RnBGMnNUZTl0VnBiQkl3OFZQakxUSmNTMTJNZSt5Z0ZaVW5KUkh1ZWJBcSswQU5rSjlyTXcKQ2RvcHpCc2wyTThlUUV3NFM3eU5NbkMrWmE3d1MwK2toS2lXMHpyNlYvdHpBVG5oOW1KSGNJYTl1MmlKRnhTcQpVUT09Ci0tLS0tRU5EIFBSSVZBVEUgS0VZLS0tLS0K';

        $user = $this->createStub(User::class);
        $user->method('getMailCryptEnabled')->willReturn(true);
        $user->method('hasMailCryptPublicKey')->willReturn(true);
        $user->method('hasMailCryptSecretBox')->willReturn(true);
        $user->method('getMailCryptPublicKey')->willReturn($publicKey);

        $userRepository = $this->createMock(UserRepository::class);
        $authenticationHandler = $this->createMock(UserAuthenticationHandler::class);
        $mailCryptKeyHandler = $this->createMock(MailCryptKeyHandler::class);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($userRepository);

        $userRepository->expects(self::once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        // The command uses $password[0], so it takes the first character
        $authenticationHandler->expects(self::once())
            ->method('authenticate')
            ->with($user, 'p')
            ->willReturn($user);

        $mailCryptKeyHandler->expects(self::once())
            ->method('decrypt')
            ->with($user, 'p')
            ->willReturn($privateKey);

        $command = new UsersMailCryptCommand($entityManager, $authenticationHandler, $mailCryptKeyHandler, $this->mailCrypt);

        $application = new Application();
        $application->addCommand($command);

        $consoleCommand = $application->find('app:users:mailcrypt');
        $commandTester = new CommandTester($consoleCommand);

        $commandTester->execute([
            '--user' => $email,
            'password' => $password,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        self::assertStringContainsString($privateKey, $output);
        self::assertStringContainsString($publicKey, $output);
    }

    public function testExecuteWithPublicKeyOnlyWhenSet(): void
    {
        $email = 'mailcrypt@example.org';
        $publicKey = 'LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlHYk1CQUdCeXFHU000OUFnRUdCU3VCQkFBakE0R0dBQVFBU09BbXlsNzdGVGdnZ05nZEN0QldBRFdBU2s0WApVckx0RnpWbjBocUZDaExjYUt3Y0VwZmI4RDlPeGFSZHJFM3ZiVmFXd1NNUEZUNHkweVhFdGRqSHZzb0JXVkp5ClVSN25td0t2dEFEWkNmYXpNQW5hS2N3YkpkalBIa0JNT0V1OGpUSnd2bVd1OEV0UHBJU29sdE02K2xmN2N3RTUKNGZaaVIzQ0d2YnRvaVJjVXFsRT0KLS0tLS1FTkQgUFVCTElDIEtFWS0tLS0tCg==';

        $user = $this->createStub(User::class);
        $user->method('getMailCryptEnabled')->willReturn(true);
        $user->method('hasMailCryptPublicKey')->willReturn(true);
        $user->method('hasMailCryptSecretBox')->willReturn(true);
        $user->method('getMailCryptPublicKey')->willReturn($publicKey);

        $this->userRepository->method('findByEmail')
            ->willReturn($user);

        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:mailcrypt');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--user' => $email,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        self::assertStringContainsString($publicKey, $output);
    }

    public function testExecuteWithMailCryptDisabled(): void
    {
        $email = 'nomailcrypt@example.org';
        $password = 'password';

        $user = $this->createStub(User::class);
        $user->method('getMailCryptEnabled')->willReturn(false);
        $user->method('hasMailCryptPublicKey')->willReturn(true);
        $user->method('hasMailCryptSecretBox')->willReturn(true);

        $this->userRepository->method('findByEmail')
            ->willReturn($user);

        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:mailcrypt');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--user' => $email,
            'password' => $password,
        ]);

        self::assertSame(1, $commandTester->getStatusCode());

        $output = $commandTester->getDisplay();
        self::assertEmpty(trim($output));
    }

    public function testExecuteWithMailCryptUnset(): void
    {
        $email = 'nomailcrypt@example.org';
        $password = 'password';

        $user = $this->createStub(User::class);
        $user->method('getMailCryptEnabled')->willReturn(true);
        $user->method('hasMailCryptPublicKey')->willReturn(false);
        $user->method('hasMailCryptSecretBox')->willReturn(false);

        $this->userRepository->method('findByEmail')
            ->willReturn($user);

        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:mailcrypt');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--user' => $email,
            'password' => $password,
        ]);

        self::assertSame(1, $commandTester->getStatusCode());

        $output = $commandTester->getDisplay();
        self::assertEmpty(trim($output));
    }

    public function testExecuteWhenMailCryptGloballyDisabled(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $userRepository = $this->createMock(UserRepository::class);

        $entityManager->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepository);

        // Create command with mailCrypt disabled
        $command = new UsersMailCryptCommand(
            $entityManager,
            $this->authenticationHandler,
            $this->mailCryptKeyHandler,
            0
        );

        $userRepository->expects(self::never())
            ->method('findByEmail');

        $application = new Application();
        $application->addCommand($command);

        $consoleCommand = $application->find('app:users:mailcrypt');
        $commandTester = new CommandTester($consoleCommand);

        $commandTester->execute([
            '--user' => 'test@example.org',
            'password' => 'password',
        ]);

        self::assertSame(1, $commandTester->getStatusCode());
    }

    public function testExecuteWithUnknownUser(): void
    {
        $email = 'unknown@example.org';

        $this->userRepository->method('findByEmail')
            ->willReturn(null);

        $application = new Application();
        $application->addCommand($this->command);

        $command = $application->find('app:users:mailcrypt');
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([
            '--user' => $email,
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('User with email', $commandTester->getDisplay());
    }

    public function testExecuteWithAuthenticationFailure(): void
    {
        $email = 'mailcrypt@example.org';
        $password = 'wrongpassword';

        $user = $this->createStub(User::class);
        $user->method('getMailCryptEnabled')->willReturn(true);
        $user->method('hasMailCryptPublicKey')->willReturn(true);
        $user->method('hasMailCryptSecretBox')->willReturn(true);

        $userRepository = $this->createMock(UserRepository::class);
        $authenticationHandler = $this->createMock(UserAuthenticationHandler::class);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($userRepository);

        $userRepository->expects(self::once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        // The command uses $password[0], so it takes the first character
        $authenticationHandler->expects(self::once())
            ->method('authenticate')
            ->with($user, 'w')
            ->willReturn(null);

        $command = new UsersMailCryptCommand($entityManager, $authenticationHandler, $this->mailCryptKeyHandler, $this->mailCrypt);

        $application = new Application();
        $application->addCommand($command);

        $consoleCommand = $application->find('app:users:mailcrypt');
        $commandTester = new CommandTester($consoleCommand);

        $commandTester->execute([
            '--user' => $email,
            'password' => $password,
        ]);

        self::assertSame(1, $commandTester->getStatusCode());
    }
}
