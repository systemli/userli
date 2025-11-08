<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use App\Factory\VoucherFactory;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;

class LoadVoucherData extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $users = $manager->getRepository(User::class)->findAll();

        for ($i = 0; $i < 1000; ++$i) {
            $voucher = VoucherFactory::create($users[random_int(1, count($users) - 1)]);

            $invitedUser = $users[random_int(0, count($users) - 1)];
            $voucher->setInvitedUser($invitedUser);
            $voucher->setRedeemedTime(new DateTime());

            $invitedUser->setInvitationVoucher($voucher);

            if (random_int(0, 100) > 50) {
                $voucher->setRedeemedTime(new DateTime(sprintf('-%d days', random_int(1, 100))));
            }

            $manager->persist($voucher);

            if (($i % 100) === 0) {
                $manager->flush();
            }
        }

        // add redeemed voucher to a suspicious parent
        $user = $manager->getRepository(User::class)->findByEmail('suspicious@example.org');
        $voucher = VoucherFactory::create($user);
        $invitedUser = $users[random_int(0, count($users) - 1)];
        $voucher->setInvitedUser($invitedUser);
        $voucher->setRedeemedTime(new DateTime(sprintf('-%d days', 100)));

        $invitedUser->setInvitationVoucher($voucher);
        $manager->persist($voucher);

        $manager->flush();
        $manager->clear();
    }

    public static function getGroups(): array
    {
        return ['advanced'];
    }

    public function getDependencies(): array
    {
        return [
            LoadUserData::class,
        ];
    }
}
