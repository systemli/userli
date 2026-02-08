<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Event\RandomAliasCreatedEvent;
use App\Helper\RandomStringGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class RandomAliasCreationListener implements EventSubscriberInterface
{
    /**
     * RandomAliasCreationListener constructor.
     */
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    public function onRandomAliasCreated(RandomAliasCreatedEvent $event): void
    {
        $alias = $event->getAlias();

        while (null !== $this->manager->getRepository(Alias::class)->findOneBySource($alias->getSource(), true)) {
            $localPart = RandomStringGenerator::generate(24, false);
            /** @var Domain $domain */
            $domain = $alias->getDomain();
            $alias->setSource($localPart.'@'.$domain->getName());
        }
    }

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            RandomAliasCreatedEvent::NAME => 'onRandomAliasCreated',
        ];
    }
}
