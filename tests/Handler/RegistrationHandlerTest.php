<?php

declare(strict_types=1);

namespace App\Tests\Handler;

use App\Entity\Domain;
use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Form\Model\Registration;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use App\Handler\RegistrationHandler;
use App\Helper\PasswordUpdater;
use App\Repository\VoucherRepository;
use App\Service\DomainGuesser;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RegistrationHandlerTest extends KernelTestCase
{
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
        $manager->method('wrapInTransaction')->willReturnCallback(static function (callable $callback) {
            return $callback();
        });
        $manager->method('persist')->willReturnCallback(static function (User $user) use ($voucher, $domain): void {
            self::assertEquals('user@example.com', $user->getEmail());
            self::assertEquals([Roles::USER], $user->getRoles());
            self::assertEquals($domain, $user->getDomain());
            self::assertEquals($voucher, $user->getInvitationVoucher());
            self::assertFalse($user->getMailCryptEnabled());
        });

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())->method('dispatch');

        $settingsService = $this->createStub(SettingsService::class);
        $settingsService->method('get')->willReturn(0);

        $handler = new RegistrationHandler(
            $manager,
            $domainGuesser,
            $eventDispatcher,
            $this->createStub(PasswordUpdater::class),
            $this->createStub(MailCryptKeyHandler::class),
            $this->createStub(RecoveryTokenHandler::class),
            $settingsService,
        );

        $registration = new Registration();
        $registration->setPassword('password');
        $registration->setEmail('user@example.com');
        $registration->setVoucher('voucher');

        $handler->handle($registration);

        self::assertNotNull($voucher->getRedeemedTime());
    }
}
