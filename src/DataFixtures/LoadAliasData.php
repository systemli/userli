<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Alias;
use App\Entity\User;
use App\Helper\RandomStringGenerator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

final class LoadAliasData extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        $user = $manager->getRepository(User::class)->findByEmail('user2@example.org');

        $alias = self::buildAlias($user, 'alias');
        $manager->persist($alias);
        $alias2 = self::buildAlias($user, 'alias2');
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

    public static function buildAlias(User $user, ?string $localPart = null): Alias
    {
        $domain = $user->getDomain();
        $alias = new Alias();
        $alias->setUser($user);
        $alias->setDomain($domain);
        $alias->setDestination($user->getEmail());

        if (null === $localPart) {
            $localPart = RandomStringGenerator::generate(Alias::RANDOM_ALIAS_LENGTH, false);
            $alias->setRandom(true);
        }

        $alias->setSource($localPart.'@'.$domain->getName());

        return $alias;
    }
}
