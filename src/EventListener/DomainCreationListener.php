<?php

namespace App\EventListener;

use App\Entity\Alias;
use App\Event\DomainCreatedEvent;
use App\Handler\WkdHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DomainCreationListener implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * @var WkdHandler
     */
    private $handler;

    public function __construct(EntityManagerInterface $manager, WkdHandler $handler)
    {
        $this->manager = $manager;
        $this->handler = $handler;
    }

    /**
     * @throws \Exception
     */
    public function onDomainCreated(DomainCreatedEvent $event)
    {
        $domain = $event->getDomain();
        $defaultDomain = $this->manager->getRepository('App:Domain')->getDefaultDomain();
        $adminAddress = 'postmaster@'.$defaultDomain;

        if (null === $domain) {
            throw new \Exception('Domain shouldn\'t be null');
        }
        if ($domain !== $defaultDomain) {
            // create postmaster alias
            // TODO: refactor this into AliasCreator
            $alias = new Alias();
            $alias->setDomain($domain);
            $alias->setSource('postmaster@'.$domain);
            $alias->setDestination($adminAddress);
            $this->manager->persist($alias);
            $this->manager->flush();
        }

        // create Web Key Directory (WKD)
        $this->handler->getDomainWkdPath($domain->getName());
    }

    public static function getSubscribedEvents()
    {
        return [
            DomainCreatedEvent::NAME => 'onDomainCreated',
        ];
    }
}
