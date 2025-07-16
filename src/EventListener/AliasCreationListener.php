<?php

namespace App\EventListener;

use Exception;
use App\Entity\Alias;
use App\Entity\Domain;
use App\Event\AliasCreatedEvent;
use App\Helper\RandomStringGenerator;
use App\Sender\AliasCreatedMessageSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class AliasCreationListener implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack              $request,
        private AliasCreatedMessageSender $sender,
        private EntityManagerInterface    $manager,
        private bool                      $sendMail,
        private string                    $defaultLocale,
    )
    {}

    /**
     * @throws Exception
     */
    public function onAliasCreated(AliasCreatedEvent $event): void
    {
        if (!$this->sendMail) {
            return;
        }

        if (null === $alias = $event->getAlias()) {
            throw new Exception('Alias should not be null');
        }

        if (null === $user = $alias->getUser()) {
            throw new Exception('User should not be null');
        }

        $locale = $this->request->getSession()->get('_locale', $this->defaultLocale);
        $this->sender->send($user, $alias, $locale);
    }

    public function onRandomAliasCreated(AliasCreatedEvent $event): void
    {
        /** @var Alias $alias */
        $alias = $event->getAlias();

        while (null !== $this->manager->getRepository(Alias::class)->findOneBySource($alias->getSource(), true)) {
            $localPart = RandomStringGenerator::generate(24, false);
            /** @var Domain $domain */
            $domain = $alias->getDomain();
            $alias->setSource($localPart.'@'.$domain->getName());
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AliasCreatedEvent::CUSTOM => 'onAliasCreated',
            AliasCreatedEvent::RANDOM => 'onRandomAliasCreated',
        ];
    }
}
