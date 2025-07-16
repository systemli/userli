<?php

namespace App\EventListener;

use App\Exception;
use App\Entity\User;
use App\Event\UserCreatedEvent;
use App\Handler\WebhookHandler;
use App\Sender\WelcomeMessageSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class UserCreationListener implements EventSubscriberInterface
{

    public function __construct(
        private WelcomeMessageSender $sender,
        private WebhookHandler $webhookHandler,
        private bool $sendMail,
        private string $defaultLocale,
    )
    {}

    public function sendWebhook(UserCreatedEvent $event): void
    {
        $this->webhookHandler->send($event->getUser(), 'user.created');
    }

    /**
     * @throws Exception
     */
    public function sendWelcomeMail(UserCreatedEvent $event): void
    {
        if (!$this->sendMail) {
            return;
        }

        /** @var User $user */
        $user = $event->getUser();
        $locale = $event->getLocale() ?? $this->defaultLocale;

        $this->sender->send($user, $locale);
        }

    public static function getSubscribedEvents(): array
    {
        return [
            UserCreatedEvent::class => [
                ['sendWebhook'],
                ['sendWelcomeMail'],
            ],
        ];
    }
}
