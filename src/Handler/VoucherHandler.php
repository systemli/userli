<?php

namespace App\Handler;

use App\Creator\VoucherCreator;
use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Exception\ValidationException;
use App\Repository\VoucherRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class VoucherHandler.
 */
class VoucherHandler
{
    private const VOUCHER_LIMIT = 3;

    private VoucherRepository $repository;
    private VoucherCreator $creator;

    /**
     * VoucherHandler constructor.
     */
    public function __construct(EntityManagerInterface $manager, VoucherCreator $creator)
    {
        $this->repository = $manager->getRepository(Voucher::class);
        $this->creator = $creator;
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

        if (null !== $user->getLastLoginTime() && count($vouchers) < self::VOUCHER_LIMIT && $user->getCreationTime() <= new \DateTime('-7 days')) {
            for ($i = count($vouchers); $i < self::VOUCHER_LIMIT; ++$i) {
                try {
                    $vouchers[] = $this->creator->create($user);
                } catch (ValidationException $e) {
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
