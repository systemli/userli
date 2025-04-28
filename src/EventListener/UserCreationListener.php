<?php

namespace App\EventListener;

use App\Event\UserCreatedEvent;
use App\Handler\WebhookHandler;
use App\Entity\User;
use App\Sender\WelcomeMessageSender;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class UserCreationListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly WebhookHandler $webhookHandler,
        private readonly RequestStack $request,
        private readonly WelcomeMessageSender $sender,
        private readonly bool $sendMail,
        private readonly string $defaultLocale,
    )
    {
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
        $locale = $this->request->getSession()->get('_locale', $this->defaultLocale);

        $this->sender->send($user, $locale);
    }

    public function sendWebhook(UserCreatedEvent $event): void
    {
        $this->webhookHandler->send($event->getUser(), 'user.created');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserCreatedEvent::REGISTRATION => [
            'sendWebhook',
            'sendWelcomeMail'
            ],
            UserCreatedEvent::RESTORE => [
            'sendWebhook'
            ],
            UserCreatedEvent::ADMIN => [
            'sendWebhook'
            ]
        ];
    }
}
