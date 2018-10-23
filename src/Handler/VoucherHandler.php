<?php

namespace App\Handler;

use App\Creator\VoucherCreator;
use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Exception\ValidationException;
use App\Repository\VoucherRepository;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class VoucherHandler.
 */
class VoucherHandler
{
    const VOUCHER_LIMIT = 3;

    /**
     * @var VoucherRepository
     */
    private $repository;
    /**
     * @var VoucherCreator
     */
    private $creator;

    /**
     * VoucherHandler constructor.
     *
     * @param ObjectManager  $manager
     * @param VoucherCreator $creator
     */
    public function __construct(ObjectManager $manager, VoucherCreator $creator)
    {
        $this->repository = $manager->getRepository('App:Voucher');
        $this->creator = $creator;
    }

    /**
     * @param User $user
     * @param bool $redeemed
     *
     * @return Voucher[]
     */
    public function getVouchersByUser(User $user, bool $redeemed = false): array
    {
        if ($user->hasRole(Roles::SUSPICIOUS)) {
            return [];
        }

        $vouchers = $this->repository->findByUser($user);

        if ($user->getCreationTime() <= new \DateTime('-7 days')) {
            if (count($vouchers) < self::VOUCHER_LIMIT) {
                for ($i = count($vouchers); $i < self::VOUCHER_LIMIT; ++$i) {
                    try {
                        $vouchers[] = $this->creator->create($user);
                    } catch (ValidationException $e) {
                        // Should not thrown
                    }
                }
            }
        }

        if (true === $redeemed) {
            return $vouchers;
        } else {
            $vouchersActive = array_filter($vouchers, function (Voucher $voucher) {
                return ($voucher->isRedeemed()) ? null : $voucher;
            });

            return $vouchersActive;
        }
    }
}
