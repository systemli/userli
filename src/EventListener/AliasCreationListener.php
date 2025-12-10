<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\AliasCreatedEvent;
use App\Sender\AliasCreatedMessageSender;
use Exception;
use Override;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class AliasCreationListener implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $request,
        private AliasCreatedMessageSender $sender,
        #[Autowire('kernel.default_locale')]
        private string $defaultLocale,
    ) {
    }

    /**
     * @throws Exception
     */
    public function onAliasCreated(AliasCreatedEvent $event): void
    {
        if (null === $alias = $event->getAlias()) {
            throw new Exception('Alias should not be null');
        }

        if (null === $user = $alias->getUser()) {
            throw new Exception('User should not be null');
        }

        $locale = $this->request->getSession()->get('_locale', $this->defaultLocale);
        $this->sender->send($user, $alias, $locale);
    }

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            AliasCreatedEvent::NAME => 'onAliasCreated',
        ];
    }
}
