<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Voucher;
use App\Message\UnlinkRedeemedVouchers;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UnlinkRedeemedVouchersHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(UnlinkRedeemedVouchers $message): void
    {
        /** @var Voucher[] $vouchers */
        $vouchers = $this->entityManager->getRepository(Voucher::class)
            ->createQueryBuilder('voucher')
            ->join('voucher.invitedUser', 'invitedUser')
            ->where('voucher.redeemedTime < :date')
            ->setParameter('date', new DateTimeImmutable('-3 months'))
            ->orderBy('voucher.redeemedTime')
            ->getQuery()
            ->getResult();

        $this->logger->info('Unlinked redeemed vouchers', ['count' => count($vouchers)]);

        foreach ($vouchers as $voucher) {
            if ($voucher->getInvitedUser() === null) {
                continue;
            }

            // Unlink the invited user after 3 months for privacy reasons
            $user = $voucher->getInvitedUser();
            $user->setInvitationVoucher();
            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();
    }
}
