<?php

declare(strict_types=1);

namespace App\Mail;

use App\Entity\User;
use App\Handler\MailHandler;
use App\Service\SettingsService;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Informs a user that the account was flagged as spammer or compromised.
 *
 * The user's locale is unknown (the role is set by an admin), so the mail
 * contains all supported languages.
 */
final readonly class SpamUserMailer
{
    private const array LOCALES = ['en', 'de'];

    public function __construct(
        private MailHandler $handler,
        private TranslatorInterface $translator,
        private SettingsService $settingsService,
    ) {
    }

    public function send(User $user): void
    {
        $this->handler->send($user->getEmail(), $this->buildBody(), $this->buildSubject());
    }

    private function buildBody(): string
    {
        $projectName = $this->settingsService->get('project_name');
        $supportAddress = (string) $this->settingsService->get('support_address');

        $parts = [];
        foreach (self::LOCALES as $locale) {
            $parts[] = trim($this->translator->trans(
                '' === $supportAddress ? 'mail.spam-body-no-support' : 'mail.spam-body',
                ['%project_name%' => $projectName, '%support_address%' => $supportAddress],
                null,
                $locale
            ));
        }

        return implode("\n\n--\n\n", $parts)."\n";
    }

    private function buildSubject(): string
    {
        $projectName = $this->settingsService->get('project_name');

        return implode(' / ', array_map(
            fn (string $locale): string => $this->translator->trans('mail.spam-subject', ['%project_name%' => $projectName], null, $locale),
            self::LOCALES
        ));
    }
}
