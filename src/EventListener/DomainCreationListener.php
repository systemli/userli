<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Event\DomainCreatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class DomainCreationListener implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $manager)
    {
    }

    public function onDomainCreated(DomainCreatedEvent $event): void
    {
        $domain = $event->getDomain();
        $defaultDomain = $this->manager->getRepository(Domain::class)->getDefaultDomain();
        $adminAddress = 'postmaster@'.$defaultDomain;

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
    }

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            DomainCreatedEvent::NAME => 'onDomainCreated',
        ];
    }
}
