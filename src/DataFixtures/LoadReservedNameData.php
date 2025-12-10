<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Creator\ReservedNameCreator;
use App\Exception\ValidationException;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

final class LoadReservedNameData extends Fixture implements FixtureGroupInterface
{
    /**
     * LoadReservedNameData constructor.
     */
    public function __construct(private readonly ReservedNameCreator $creator)
    {
    }

    /**
     * @throws ValidationException
     */
    #[Override]
    public function load(ObjectManager $manager): void
    {
        $handle = fopen(
            __DIR__.'/../../config/reserved_names.txt',
            'r'
        );

        while ($line = fgets($handle)) {
            $name = trim($line);
            if (empty($name)) {
                continue;
            }

            if ('#' === $name[0]) {
                // filter out comments
                continue;
            }

            $this->creator->create($name);
        }
    }

    #[Override]
    public static function getGroups(): array
    {
        return ['basic'];
    }
}
