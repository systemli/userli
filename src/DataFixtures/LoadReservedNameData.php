<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\ReservedName;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

final class LoadReservedNameData extends Fixture implements FixtureGroupInterface
{
    private const array RESERVED_NAMES = [
        'admin',
        'root',
        'postmaster',
        'abuse',
        'webmaster',
    ];

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $repository = $manager->getRepository(ReservedName::class);

        foreach (self::RESERVED_NAMES as $name) {
            // Skip names already present (e.g. seeded by migrations) so the
            // fixture stays idempotent when loaded with --append.
            if (null !== $repository->findOneBy(['name' => $name])) {
                continue;
            }

            $reservedName = new ReservedName();
            $reservedName->setName($name);
            $manager->persist($reservedName);
        }

        $manager->flush();
    }

    #[Override]
    public static function getGroups(): array
    {
        return ['basic'];
    }
}
