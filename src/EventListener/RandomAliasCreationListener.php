<?php

namespace App\EventListener;

use App\Entity\Alias;
use App\Event\RandomAliasCreatedEvent;
use App\Helper\RandomStringGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RandomAliasCreationListener implements EventSubscriberInterface
{
    private EntityManagerInterface $manager;

    /**
     * RandomAliasCreationListener constructor.
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function onRandomAliasCreated(RandomAliasCreatedEvent $event): void
    {
        $alias = $event->getAlias();

        while (null !== $this->manager->getRepository(Alias::class)->findOneBySource($alias->getSource())) {
            $localPart = RandomStringGenerator::generate(24, false);
            $domain = $alias->getDomain();
            $alias->setSource($localPart.'@'.$domain->getName());
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            RandomAliasCreatedEvent::NAME => 'onRandomAliasCreated',
        ];
    }
}
