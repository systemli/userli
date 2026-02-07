<?php

declare(strict_types=1);

namespace App\Handler;

use App\Creator\VoucherCreator;
use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Exception\ValidationException;
use App\Repository\VoucherRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class VoucherHandler.
 */
final class VoucherHandler
{
    private const VOUCHER_LIMIT = 3;

    private readonly VoucherRepository $repository;

    /**
     * VoucherHandler constructor.
     */
    public function __construct(EntityManagerInterface $manager, private readonly VoucherCreator $creator)
    {
        $this->repository = $manager->getRepository(Voucher::class);
    }

    /**
     * @return Voucher[]
     */
    public function getVouchersByUser(User $user, bool $redeemed = false): array
    {
        if ($user->hasRole(Roles::SUSPICIOUS)) {
            return [];
        }

        $vouchers = $this->repository->findByUser($user);

        if (null !== $user->getLastLoginTime() && count($vouchers) < self::VOUCHER_LIMIT && $user->getCreationTime() <= new DateTimeImmutable('-7 days')) {
            for ($i = count($vouchers); $i < self::VOUCHER_LIMIT; ++$i) {
                try {
                    $vouchers[] = $this->creator->create($user);
                } catch (ValidationException) {
                    // Should not throw
                }
            }
        }

        if (true === $redeemed) {
            return $vouchers;
        }

        return array_filter($vouchers, static function (Voucher $voucher) {
            return ($voucher->isRedeemed()) ? null : $voucher;
        });
    }
}
