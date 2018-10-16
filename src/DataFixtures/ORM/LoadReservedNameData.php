<?php

namespace App\DataFixtures\ORM;

use App\Entity\ReservedName;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * @author doobry <doobry@systemli.org>
 */
class LoadReservedNameData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $handle = fopen(
            dirname(__FILE__).'/../../../config/reserved_names.txt',
            'r'
        );

        while ($line = fgets($handle)) {
            $name = trim($line);
            if (empty($name)) {
                continue;
            } elseif (substr($name, 0, 1) === "#") {
                // filter out comments
                continue;
            }

            $reservedName = new ReservedName();
            $reservedName->setName($name);

            $manager->persist($reservedName);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 5;
    }
}
