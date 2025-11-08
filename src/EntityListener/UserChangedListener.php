<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Handler\SuspiciousChildrenHandler;
use App\Repository\VoucherRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: User::class)]
class UserChangedListener
{
    private VoucherRepository $voucherRepository;

    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly SuspiciousChildrenHandler $suspiciousChildrenHandler,
    ) {
        $this->voucherRepository = $this->manager->getRepository(Voucher::class);
    }

    public function preUpdate(User $user, PreUpdateEventArgs $args): void
    {
        // Get changed roles from user.
        $changeArray = $args->getEntityChangeSet();
        if (array_key_exists('roles', $changeArray)) {
            [$rolesBefore, $rolesAfter] = $changeArray['roles'];

            // If `ROLE_SUSPICIOUS` is added, check if other users got invited by that user
            if (!in_array(Roles::SUSPICIOUS, $rolesBefore, true)
                && in_array(Roles::SUSPICIOUS, $rolesAfter, true)) {
                $suspiciousChildren = [];
                $redeemedVouchers = $this->voucherRepository->getRedeemedVouchersByUser($user);
                foreach ($redeemedVouchers as $voucher) {
                    if ($invitedUser = $voucher->getInvitedUser()) {
                        $suspiciousChildren[$invitedUser->getUserIdentifier()] = $user->getUserIdentifier();
                    }
                }

                $this->suspiciousChildrenHandler->sendReport($suspiciousChildren);
            }
        }
    }
}
