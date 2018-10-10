<?php

namespace App\EventListener;

use App\Event\AliasCreatedEvent;
use App\Helper\RandomStringGenerator;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author doobry <doobry@systemli.org>
 */
class AliasCreationListener implements EventSubscriberInterface
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * AliasCreationListener constructor.
     *
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param AliasCreatedEvent $event
     */
    public function onAliasCreated(AliasCreatedEvent $event)
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
            AliasCreatedEvent::NAME => 'onAliasCreated',
        ];
    }
}
