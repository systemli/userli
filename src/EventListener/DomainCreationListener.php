<?php

namespace App\EventListener;

use App\Entity\Alias;
use App\Event\DomainCreatedEvent;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DomainCreationListener implements EventSubscriberInterface
{
    /**
     * @var ObjectManager
     */
    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
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
            $alias = new Alias();
            $alias->setDomain($domain);
            $alias->setSource('postmaster@'.$domain);
            $alias->setDestination($adminAddress);
            $this->manager->persist($alias);
            $this->manager->flush();
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            DomainCreatedEvent::NAME => 'onDomainCreated',
        ];
    }
}
