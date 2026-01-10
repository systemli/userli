<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use App\Factory\VoucherFactory;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Override;

final class LoadVoucherData extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    /**
     * @throws Exception
     */
    #[Override]
    public function load(ObjectManager $manager): void
    {
        assert($manager instanceof EntityManagerInterface);

        $userIds = $manager->getRepository(User::class)->createQueryBuilder('u')
            ->select('u.id')
            ->getQuery()
            ->getSingleColumnResult();

        $suspiciousUser = $manager->getRepository(User::class)->findByEmail('suspicious@example.org');
        $suspiciousUserId = $suspiciousUser->getId();

        for ($i = 0; $i < 1000; ++$i) {
            $user = $manager->getReference(User::class, $userIds[array_rand($userIds)]);
            $voucher = VoucherFactory::create($user);

            $invitedUser = $manager->getReference(User::class, $userIds[array_rand($userIds)]);
            $voucher->setInvitedUser($invitedUser);
            $voucher->setRedeemedTime(new DateTime());

            $invitedUser->setInvitationVoucher($voucher);

            if (random_int(0, 100) > 50) {
                $voucher->setRedeemedTime(new DateTime(sprintf('-%d days', random_int(1, 100))));
            }

            $manager->persist($voucher);

            if (($i % 250) === 0) {
                $manager->flush();
                $manager->clear();
            }
        }

        // add redeemed voucher to a suspicious parent
        $user = $manager->getReference(User::class, $suspiciousUserId);
        $voucher = VoucherFactory::create($user);
        $invitedUser = $manager->getReference(User::class, $userIds[array_rand($userIds)]);
        $voucher->setInvitedUser($invitedUser);
        $voucher->setRedeemedTime(new DateTime(sprintf('-%d days', 100)));

        $invitedUser->setInvitationVoucher($voucher);
        $manager->persist($voucher);

        $manager->flush();
        $manager->clear();
    }

    #[Override]
    public static function getGroups(): array
    {
        return ['advanced'];
    }

    #[Override]
    public function getDependencies(): array
    {
        return [
            LoadUserData::class,
        ];
    }
}
