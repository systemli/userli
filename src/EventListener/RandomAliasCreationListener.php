<?php

namespace App\EventListener;

use App\Event\RandomAliasCreatedEvent;
use App\Helper\RandomStringGenerator;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author doobry <doobry@systemli.org>
 */
class RandomAliasCreationListener implements EventSubscriberInterface
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * RandomAliasCreationListener constructor.
     *
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param RandomAliasCreatedEvent $event
     */
    public function onRandomAliasCreated(RandomAliasCreatedEvent $event)
    {
        $alias = $event->getAlias();

        while (null !== $this->manager->getRepository('App:Alias')->findOneBySource($alias->getSource())) {
            $localPart = RandomStringGenerator::generate(24, false);
            $domain = $alias->getDomain();
            $alias->setSource($localPart."@".$domain->getName());
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            RandomAliasCreatedEvent::NAME => 'onRandomAliasCreated',
        ];
    }
}
