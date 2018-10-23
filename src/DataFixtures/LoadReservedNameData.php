<?php

namespace App\DataFixtures;

use App\Creator\ReservedNameCreator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * @author doobry <doobry@systemli.org>
 */
class LoadReservedNameData extends Fixture implements OrderedFixtureInterface
{
    /**
     * @var ReservedNameCreator
     */
    private $creator;

    /**
     * LoadReservedNameData constructor.
     *
     * @param ReservedNameCreator $creator
     */
    public function __construct(ReservedNameCreator $creator)
    {
        $this->creator = $creator;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \App\Exception\ValidationException
     */
    public function load(ObjectManager $manager)
    {
        $handle = fopen(
            dirname(__FILE__).'/../../config/reserved_names.txt',
            'r'
        );

        while ($line = fgets($handle)) {
            $name = trim($line);
            if (empty($name)) {
                continue;
            } elseif ('#' === substr($name, 0, 1)) {
                // filter out comments
                continue;
            }

            $this->creator->create($name);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 5;
    }
}
