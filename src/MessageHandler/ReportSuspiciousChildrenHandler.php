<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\User;
use App\Entity\Voucher;
use App\Handler\SuspiciousChildrenHandler;
use App\Message\ReportSuspiciousChildren;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ReportSuspiciousChildrenHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SuspiciousChildrenHandler $suspiciousChildrenHandler,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ReportSuspiciousChildren $message): void
    {
        $user = $this->entityManager->getRepository(User::class)->find($message->userId);

        if (null === $user) {
            $this->logger->warning('User not found for suspicious children report', ['userId' => $message->userId]);

            return;
        }

        $suspiciousChildren = [];
        $redeemedVouchers = $this->entityManager->getRepository(Voucher::class)->getRedeemedVouchersByUser($user);
        foreach ($redeemedVouchers as $voucher) {
            if ($invitedUser = $voucher->getInvitedUser()) {
                $suspiciousChildren[$invitedUser->getUserIdentifier()] = $user->getUserIdentifier();
            }
        }

        if ([] !== $suspiciousChildren) {
            $this->suspiciousChildrenHandler->sendReport($suspiciousChildren);
            $this->logger->info('Reported suspicious children', ['email' => $user->getEmail(), 'count' => count($suspiciousChildren)]);
        }
    }
}
