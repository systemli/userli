<?php

namespace App\DataFixtures\ORM;

use App\Entity\ReservedName;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

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
        $reservedNames = Yaml::parsefile(dirname(__FILE__).'/../../../config/reserved_names.yml');
        foreach ($reservedNames['reservedNames'] as $name) {
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
