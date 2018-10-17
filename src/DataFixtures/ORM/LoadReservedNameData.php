<?php

namespace App\DataFixtures\ORM;

use App\Creator\ReservedNameCreator;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author doobry <doobry@systemli.org>
 */
class LoadReservedNameData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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

            $this->getReservedNameCreator()->create($name);
        }
    }

    /**
     * @return ReservedNameCreator
     */
    private function getReservedNameCreator()
    {
        return $this->container->get(ReservedNameCreator::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 5;
    }
}
