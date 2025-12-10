<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Enum\MailCrypt;
use App\Event\LoginEvent;
use App\Handler\MailCryptKeyHandler;
use App\Service\UserLastLoginUpdateService;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

readonly class LoginListener implements EventSubscriberInterface
{
    private MailCrypt $mailCrypt;

    public function __construct(
        private UserLastLoginUpdateService $userLastLoginUpdateService,
        private MailCryptKeyHandler $mailCryptKeyHandler,
        private LoggerInterface $logger,
        #[Autowire(env: 'MAIL_CRYPT')]
        private int $mailCryptEnv,
    ) {
        $this->mailCrypt = MailCrypt::from($this->mailCryptEnv);
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if (!$user instanceof User) {
            return;
        }

        $password = $event->getRequest()->get('_password');
        if ($password == null) {
            $this->logger->error(
                '"_password" should not be null.',
                ['email' => $user->getEmail()]
            );

            return;
        }

        $this->handleLogin($user, $password);
    }

    public function onAuthenticationHandlerSuccess(LoginEvent $event): void
    {
        $this->handleLogin($event->getUser(), $event->getPlainPassword());
    }

    private function handleLogin(User $user, ?string $password): void
    {
        if ($this->mailCrypt === MailCrypt::ENABLED_ENFORCE_ALL_USERS && null !== $password) {
            $this->enableMailCrypt($user, $password);
        }

        $this->userLastLoginUpdateService->updateLastLogin($user);
    }

    private function enableMailCrypt(User $user, string $password): void
    {
        if ($user->getMailCryptEnabled() || null !== $user->getMailCryptPublicKey()) {
            return;
        }

        $this->mailCryptKeyHandler->create($user, $password, true);
    }

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
            LoginEvent::NAME => 'onAuthenticationHandlerSuccess',
        ];
    }
}
