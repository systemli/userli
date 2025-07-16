<?php

namespace App\EventListener;

use Exception;
use App\Entity\Alias;
use App\Entity\Domain;
use App\Event\DomainCreatedEvent;
use App\Handler\WkdHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DomainCreationListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly WkdHandler $handler
    )
    {
    }

    /**
     * @throws Exception
     */
    public function onDomainCreated(DomainCreatedEvent $event): void
    {
        $defaultDomain = $this->manager->getRepository(Domain::class)->getDefaultDomain();
        $adminAddress = 'postmaster@'.$defaultDomain;

        ;
        if (null === $domain = $event->getDomain()) {
            throw new Exception("Domain shouldn't be null");
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

    public static function getSubscribedEvents(): array
    {
        return [
            DomainCreatedEvent::NAME => 'onDomainCreated',
        ];
    }
}
