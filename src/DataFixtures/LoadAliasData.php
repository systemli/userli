<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use App\Factory\AliasFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

class LoadAliasData extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    #[Override]
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

    #[Override]
    public static function getGroups(): array
    {
        return ['basic'];
    }

    #[Override]
    public function getDependencies(): array
    {
        return [
            LoadUserData::class,
        ];
    }
}
