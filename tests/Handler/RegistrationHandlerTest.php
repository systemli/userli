<?php

namespace App\Tests\Handler;

use App\Entity\Domain;
use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Event\Events;
use App\Event\UserEvent;
use App\Repository\VoucherRepository;
use Exception;
use App\Form\Model\Registration;
use App\Guesser\DomainGuesser;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use App\Handler\RegistrationHandler;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RegistrationHandlerTest extends KernelTestCase
{
    public function testHandleWithDisabledRegistration()
    {
        $handler = new RegistrationHandler(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(DomainGuesser::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(PasswordUpdater::class),
            $this->createMock(MailCryptKeyHandler::class),
            $this->createMock(RecoveryTokenHandler::class),
            false,
            false
        );

        $this->expectException(Exception::class);
        $handler->handle(new Registration());
    }

    public function testHandleWithEnabledRegistration()
    {
        $domain = new Domain();
        $domainGuesser = $this->createMock(DomainGuesser::class);
        $domainGuesser->method('guess')->willReturn($domain);

        $voucher = new Voucher();
        $voucherRepository = $this->createMock(VoucherRepository::class);
        $voucherRepository->method('findByCode')->willReturn($voucher);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturnMap([
            [Voucher::class, $voucherRepository],
        ]);
        $manager->method('persist')->willReturnCallback(function (User $user) use ($voucher, $domain): void {
            $this->assertEquals("user@example.com", $user->getEmail());
            $this->assertEquals([Roles::USER], $user->getRoles());
            $this->assertEquals($domain, $user->getDomain());
            $this->assertEquals($voucher, $user->getInvitationVoucher());
            $this->assertFalse($user->getMailCryptEnabled());
        });

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())->method('dispatch');

        $handler = new RegistrationHandler(
            $manager,
            $domainGuesser,
            $eventDispatcher,
            $this->createMock(PasswordUpdater::class),
            $this->createMock(MailCryptKeyHandler::class),
            $this->createMock(RecoveryTokenHandler::class),
            true,
            false
        );

        $registration = new Registration();
        $registration->setPlainPassword('password');
        $registration->setEmail('user@example.com');
        $registration->setVoucher("voucher");

        $handler->handle($registration);

        $this->assertNotNull($voucher->getRedeemedTime());
    }
}
