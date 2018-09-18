<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Alias;
use AppBundle\Repository\DomainRepository;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author louis <louis@systemli.org>
 */
class LoadAliasData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $domain = $this->getRepository()->findByName('example.org');

        for ($i = 1; $i < 5; ++$i) {
            $alias = new Alias();
            $alias->setSource(sprintf('alias%d@' . $domain, $i));
            $alias->setDestination('admin@' . $domain);
            $alias->setDomain($domain);

            $manager->persist($alias);
            $manager->flush();
        }
    }

    /**
     * @return DomainRepository
     */
    private function getRepository()
    {
        return $this->container->get('doctrine')->getRepository('AppBundle:Domain');
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 4;
    }
}
