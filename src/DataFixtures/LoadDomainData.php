<?php

namespace App\DataFixtures;

use App\Entity\Domain;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * @author louis <louis@systemli.org>
 */
class LoadDomainData extends Fixture implements OrderedFixtureInterface
{
    private $domains = [
        'example.com',
        'example.org',
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->domains as $name) {
            $domain = new Domain();
            $domain->setName($name);

            $manager->persist($domain);
            $manager->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }
}
