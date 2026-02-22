<?php

declare(strict_types=1);

namespace App\Remover;

use App\Entity\User;
use App\Entity\Voucher;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class VoucherRemover.
 */
final readonly class VoucherRemover
{
    /**
     * VoucherRemover constructor.
     */
    public function __construct(private EntityManagerInterface $manager)
    {
    }

    public function removeUnredeemedVouchersByUser(User $user): void
    {
        $this->removeUnredeemedVouchersByUsers([$user]);
    }

    public function removeUnredeemedVouchersByUsers(array $users): void
    {
        $this->manager->getRepository(Voucher::class)
            ->createQueryBuilder('v')
            ->delete()
            ->where('v.redeemedTime IS NULL')
            ->andWhere('v.user IN (:users)')
            ->setParameter('users', $users)
            ->getQuery()
            ->execute();
    }
}
