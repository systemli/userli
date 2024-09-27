<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Factory\AliasFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadAliasData extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $user = $manager->getRepository(User::class)->findByEmail('user2@example.org');

        $alias = AliasFactory::create($user, 'alias');
        $manager->persist($alias);
        $alias2 = AliasFactory::create($user, 'alias2');
        $manager->persist($alias2);

        $manager->flush();
        $manager->clear();
    }

    public static function getGroups(): array
    {
        return ['basic'];
    }

    public function getDependencies(): array
    {
        return [
            LoadUserData::class,
        ];
    }
}
