<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Domain;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * @author louis <louis@systemli.org>
 */
class LoadDomainData extends AbstractFixture implements OrderedFixtureInterface
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
