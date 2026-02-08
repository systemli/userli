<?php

declare(strict_types=1);

namespace App\Tests\Handler;

use App\Entity\Domain;
use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Form\Model\Registration;
use App\Guesser\DomainGuesser;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use App\Handler\RegistrationHandler;
use App\Helper\PasswordUpdater;
use App\Repository\VoucherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RegistrationHandlerTest extends KernelTestCase
{
    public function testHandleWithDisabledRegistration(): void
    {
        $handler = new RegistrationHandler(
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(DomainGuesser::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(PasswordUpdater::class),
            $this->createStub(MailCryptKeyHandler::class),
            $this->createStub(RecoveryTokenHandler::class),
            false,
            false
        );

        $this->expectException(Exception::class);
        $handler->handle(new Registration());
    }

    public function testHandleWithEnabledRegistration(): void
    {
        $domain = new Domain();
        $domainGuesser = $this->createStub(DomainGuesser::class);
        $domainGuesser->method('guess')->willReturn($domain);

        $voucher = new Voucher('code');
        $voucherRepository = $this->createStub(VoucherRepository::class);
        $voucherRepository->method('findByCode')->willReturn($voucher);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturnMap([
            [Voucher::class, $voucherRepository],
        ]);
        $manager->method('persist')->willReturnCallback(static function (User $user) use ($voucher, $domain): void {
            self::assertEquals('user@example.com', $user->getEmail());
            self::assertEquals([Roles::USER], $user->getRoles());
            self::assertEquals($domain, $user->getDomain());
            self::assertEquals($voucher, $user->getInvitationVoucher());
            self::assertFalse($user->getMailCryptEnabled());
        });

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())->method('dispatch');

        $handler = new RegistrationHandler(
            $manager,
            $domainGuesser,
            $eventDispatcher,
            $this->createStub(PasswordUpdater::class),
            $this->createStub(MailCryptKeyHandler::class),
            $this->createStub(RecoveryTokenHandler::class),
            true,
            false
        );

        $registration = new Registration();
        $registration->setPassword('password');
        $registration->setEmail('user@example.com');
        $registration->setVoucher('voucher');

        $handler->handle($registration);

        self::assertNotNull($voucher->getRedeemedTime());
    }
}
