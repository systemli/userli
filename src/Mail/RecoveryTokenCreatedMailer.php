<?php

declare(strict_types=1);

namespace App\Mail;

use App\Entity\User;
use App\Handler\MailHandler;
use App\Service\SettingsService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Tells the account owner that a new recovery token was created, so they notice if it wasn't them.
 */
final readonly class RecoveryTokenCreatedMailer
{
    public function __construct(
        private MailHandler $handler,
        private TranslatorInterface $translator,
        private SettingsService $settingsService,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function send(User $user, string $locale): void
    {
        $email = $user->getEmail();

        // Host from the admin-editable `app_url` setting, path from the router (see RecoveryProcessMailer)
        $appUrl = rtrim((string) $this->settingsService->get('app_url'), '/');

        $body = $this->translator->trans(
            'mail.recovery-token-created-body',
            [
                '%project_name%' => $this->settingsService->get('project_name'),
                '%email%' => $email,
                '%password_url%' => $appUrl.$this->urlGenerator->generate('account_password', [], UrlGeneratorInterface::ABSOLUTE_PATH),
                '%recovery_token_url%' => $appUrl.$this->urlGenerator->generate('account_recovery_token', [], UrlGeneratorInterface::ABSOLUTE_PATH),
            ],
            null,
            $locale
        );
        $subject = $this->translator->trans('mail.recovery-token-created-subject', ['%email%' => $email], null, $locale);

        $this->handler->send($email, $body, $subject);
    }
}
