<?php

namespace App\EventListener;

use App\Creator\AliasCreator;
use App\Event\AliasEvent;
use App\Event\Events;
use App\Helper\RandomStringGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author doobry <doobry@systemli.org>
 */
class AliasCreationListener implements EventSubscriberInterface
{
    /**
     * @var AliasCreator
     */
    private $creator;

    /**
     * AliasCreationListener constructor.
     * @param AliasCreator $creator
     */
    public function __construct(AliasCreator $creator)
    {
        $this->creator = $creator;
    }

    /**
     * @param AliasEvent $event
     */
    public function onAliasCreated(AliasEvent $event)
    {
        $alias = $event->getAlias();

        while (false === $this->creator->validateUnique($alias)) {
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
            Events::MAIL_ALIAS_CREATED => 'onAliasCreated',
        ];
    }
}
