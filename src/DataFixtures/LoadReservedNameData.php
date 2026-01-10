<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Factory\ReservedNameFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

final class LoadReservedNameData extends Fixture implements FixtureGroupInterface
{
    #[Override]
    public function load(ObjectManager $manager): void
    {
        $handle = fopen(
            __DIR__.'/../../config/reserved_names.txt',
            'r'
        );

        $count = 0;
        while ($line = fgets($handle)) {
            $name = trim($line);
            if (empty($name) || '#' === $name[0]) {
                continue;
            }

            $reservedName = ReservedNameFactory::create($name);
            $manager->persist($reservedName);
            ++$count;

            if (($count % 250) === 0) {
                $manager->flush();
                $manager->clear();
            }
        }

        fclose($handle);
        $manager->flush();
        $manager->clear();
    }

    #[Override]
    public static function getGroups(): array
    {
        return ['basic'];
    }
}
