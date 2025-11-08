<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use App\Factory\AliasFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadRandomAliasData extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $user = $manager->getRepository(User::class)->findByEmail('admin@example.org');

        for ($i = 1; $i < 5; ++$i) {
            $alias = AliasFactory::create($user, null);

            $manager->persist($alias);
        }

        $users = $manager->getRepository(User::class)->findAll();

        for ($i = 1; $i < 500; ++$i) {
            $alias = AliasFactory::create($users[random_int(0, count($users) - 1)], 'alias'.$i);

            $manager->persist($alias);

            if (($i % 100) === 0) {
                $manager->flush();
            }
        }

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
