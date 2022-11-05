<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Voucher;
use App\Factory\VoucherFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadVoucherData extends Fixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    private ContainerInterface $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        $users = $manager->getRepository(User::class)->findAll();

        for ($i = 0; $i < 1000; ++$i) {
            $voucher = VoucherFactory::create($users[random_int(1, count($users) - 1)]);

            $invitedUser = $users[random_int(0, count($users) - 1)];
            $voucher->setInvitedUser($invitedUser);
            $voucher->setRedeemedTime(new \DateTime());

            $invitedUser->setInvitationVoucher($voucher);

            if (random_int(0, 100) > 50) {
                $voucher->setRedeemedTime(new \DateTime(sprintf('-%d days', random_int(1, 100))));
            }

            $manager->persist($voucher);
        }

        // add redeemed voucher to a suspicious parent
        $user = $manager->getRepository(User::class)->findByEmail('suspicious@example.org');
        $voucher = VoucherFactory::create($user);
        $invitedUser = $users[random_int(0, count($users) - 1)];
        $voucher->setInvitedUser($invitedUser);
        $voucher->setRedeemedTime(new \DateTime(sprintf('-%d days', 100)));
        $invitedUser->setInvitationVoucher($voucher);
        $manager->persist($voucher);

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder(): int
    {
        return 3;
    }
}
