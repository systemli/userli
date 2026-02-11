<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\User;
use App\Entity\Voucher;
use App\Handler\SuspiciousChildrenHandler;
use App\Message\ReportSuspiciousChildren;
use App\MessageHandler\ReportSuspiciousChildrenHandler;
use App\Repository\UserRepository;
use App\Repository\VoucherRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ReportSuspiciousChildrenHandlerTest extends TestCase
{
    public function testReportsSuspiciousChildren(): void
    {
        $userId = 42;
        $message = new ReportSuspiciousChildren($userId);

        $user = new User('user@example.org');
        $user->setId($userId);

        $invitedUser1 = new User('invited1@example.org');
        $voucher1 = new Voucher('code1');
        $voucher1->setInvitedUser($invitedUser1);
        $invitedUser2 = new User('invited2@example.org');
        $voucher2 = new Voucher('code2');
        $voucher2->setInvitedUser($invitedUser2);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects(self::once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        $voucherRepository = $this->createStub(VoucherRepository::class);
        $voucherRepository->method('getRedeemedVouchersByUser')
            ->with($user)
            ->willReturn([$voucher1, $voucher2]);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')
            ->willReturnMap([
                [User::class, $userRepository],
                [Voucher::class, $voucherRepository],
            ]);

        $suspiciousChildrenHandler = $this->createMock(SuspiciousChildrenHandler::class);
        $suspiciousChildrenHandler->expects(self::once())
            ->method('sendReport')
            ->with([
                $invitedUser1->getEmail() => $user->getEmail(),
                $invitedUser2->getEmail() => $user->getEmail(),
            ]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with('Reported suspicious children', ['email' => 'user@example.org', 'count' => 2]);

        $handler = new ReportSuspiciousChildrenHandler($em, $suspiciousChildrenHandler, $logger);
        $handler($message);
    }

    public function testSkipsNonExistentUser(): void
    {
        $userId = 999;
        $message = new ReportSuspiciousChildren($userId);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects(self::once())
            ->method('find')
            ->with($userId)
            ->willReturn(null);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->with(User::class)->willReturn($userRepository);

        $suspiciousChildrenHandler = $this->createMock(SuspiciousChildrenHandler::class);
        $suspiciousChildrenHandler->expects(self::never())
            ->method('sendReport');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with('User not found for suspicious children report', ['userId' => $userId]);

        $handler = new ReportSuspiciousChildrenHandler($em, $suspiciousChildrenHandler, $logger);
        $handler($message);
    }

    public function testSkipsWhenNoSuspiciousChildren(): void
    {
        $userId = 42;
        $message = new ReportSuspiciousChildren($userId);

        $user = new User('user@example.org');
        $user->setId($userId);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects(self::once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        $voucherRepository = $this->createStub(VoucherRepository::class);
        $voucherRepository->method('getRedeemedVouchersByUser')
            ->willReturn([]);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')
            ->willReturnMap([
                [User::class, $userRepository],
                [Voucher::class, $voucherRepository],
            ]);

        $suspiciousChildrenHandler = $this->createMock(SuspiciousChildrenHandler::class);
        $suspiciousChildrenHandler->expects(self::never())
            ->method('sendReport');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())
            ->method('info');

        $handler = new ReportSuspiciousChildrenHandler($em, $suspiciousChildrenHandler, $logger);
        $handler($message);
    }
}
