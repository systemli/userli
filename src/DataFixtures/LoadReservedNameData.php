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
        foreach (self::RESERVED_NAMES as $name) {
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
