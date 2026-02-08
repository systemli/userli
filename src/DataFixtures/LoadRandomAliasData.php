<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use App\Factory\AliasFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

final class LoadRandomAliasData extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        assert($manager instanceof EntityManagerInterface);

        $user = $manager->getRepository(User::class)->findByEmail('admin@example.org');

        for ($i = 1; $i < 5; ++$i) {
            $alias = AliasFactory::create($user, null);
            $manager->persist($alias);
        }

        $manager->flush();

        $userIds = $manager->getRepository(User::class)->createQueryBuilder('u')
            ->select('u.id')
            ->getQuery()
            ->getSingleColumnResult();

        for ($i = 1; $i < 500; ++$i) {
            $userId = $userIds[array_rand($userIds)];
            $user = $manager->getReference(User::class, $userId);
            $alias = AliasFactory::create($user, 'alias'.$i);

            $manager->persist($alias);

            if (($i % 250) === 0) {
                $manager->flush();
                $manager->clear();
            }
        }

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
