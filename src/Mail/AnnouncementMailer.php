<?php

declare(strict_types=1);

namespace App\Mail;

use App\Handler\MailHandler;
use App\Service\SettingsService;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Sends an admin-written announcement to a user of a domain.
 *
 * The text is sent as written. A footer in all supported languages marks the
 * mail as official announcement, because the user's locale is unknown.
 */
final readonly class AnnouncementMailer
{
    private const array LOCALES = ['en', 'de'];

    public function __construct(
        private MailHandler $handler,
        private TranslatorInterface $translator,
        private SettingsService $settingsService,
    ) {
    }

    public function send(string $recipient, string $subject, string $body): void
    {
        $this->handler->send($recipient, $this->buildBody($body), $subject);
    }

    public function buildBody(string $body): string
    {
        $parameters = ['%project_name%' => $this->settingsService->get('project_name')];

        $footer = array_map(
            fn (string $locale): string => trim($this->translator->trans('mail.announcement-footer', $parameters, null, $locale)),
            self::LOCALES
        );

        return rtrim($body)."\n\n-- \n".implode("\n", $footer)."\n";
    }
}
