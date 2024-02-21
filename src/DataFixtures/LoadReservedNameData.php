<?php

namespace App\DataFixtures;

use App\Creator\ReservedNameCreator;
use App\Exception\ValidationException;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadReservedNameData extends Fixture implements OrderedFixtureInterface
{
    /**
     * LoadReservedNameData constructor.
     */
    public function __construct(private readonly ReservedNameCreator $creator)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @throws ValidationException
     */
    public function load(ObjectManager $manager): void
    {
        $handle = fopen(
            dirname(__FILE__).'/../../config/reserved_names.txt',
            'rb'
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

    /**
     * {@inheritdoc}
     */
    public function getOrder(): int
    {
        return 5;
    }
}
