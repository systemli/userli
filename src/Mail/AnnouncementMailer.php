<?php

declare(strict_types=1);

namespace App\Mail;

use App\Handler\MailHandler;
use App\Service\SettingsService;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Sends an admin-written announcement to a user of a domain.
 *
 * The text is sent as written. A footer in all supported languages names the
 * sender, because the user's locale is unknown.
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

    /**
     * @param string|null $domainName domain whose admins wrote the announcement, null for the project's admins
     */
    public function send(string $recipient, string $subject, string $body, ?string $domainName): void
    {
        $this->handler->send($recipient, $this->buildBody($body, $domainName), $subject);
    }

    public function buildBody(string $body, ?string $domainName): string
    {
        // Domain admins must not speak with the project's voice
        [$key, $parameters] = null === $domainName
            ? ['mail.announcement-footer', ['%project_name%' => $this->settingsService->get('project_name')]]
            : ['mail.announcement-footer-domain', ['%domain%' => $domainName]];

        $footer = array_map(
            fn (string $locale): string => trim($this->translator->trans($key, $parameters, null, $locale)),
            self::LOCALES
        );

        return rtrim($body)."\n\n-- \n".implode("\n", $footer)."\n";
    }
}
