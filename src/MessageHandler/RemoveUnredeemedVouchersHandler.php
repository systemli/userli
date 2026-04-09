<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Voucher;
use App\Message\RemoveUnredeemedVouchers;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(sign: true)]
final readonly class RemoveUnredeemedVouchersHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(RemoveUnredeemedVouchers $message): void
    {
        /** @var Voucher[] $vouchers */
        $vouchers = $this->entityManager->getRepository(Voucher::class)
            ->createQueryBuilder('v')
            ->join('v.user', 'u')
            ->where('v.redeemedTime IS NULL')
            ->andWhere('u.deleted = true')
            ->getQuery()
            ->getResult();

        foreach ($vouchers as $voucher) {
            $this->entityManager->remove($voucher);
        }

        $this->entityManager->flush();

        $this->logger->info('Removed unredeemed vouchers of deleted users', ['count' => count($vouchers)]);
    }
}
