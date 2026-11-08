<?php

declare(strict_types=1);

namespace App\Mail;

use App\Entity\OpenPgpKey;
use App\Entity\User;
use App\Handler\MailHandler;
use App\Service\SettingsService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Informs a user that the OpenPGP key on their e-mail address expired.
 *
 * The user's locale is unknown at this point (the notification is sent by
 * a background worker), so the mail contains all supported languages.
 */
final readonly class UserKeyExpiredMailer
{
    private const array LOCALES = ['en', 'de'];

    public function __construct(
        private MailHandler $handler,
        private TranslatorInterface $translator,
        private SettingsService $settingsService,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function sendToUser(User $user, OpenPgpKey $key): void
    {
        $this->handler->send($user->getEmail(), $this->buildBody($key), $this->buildSubject($key));
    }

    private function buildBody(OpenPgpKey $key): string
    {
        $openpgpUrl = rtrim((string) $this->settingsService->get('app_url'), '/')
            . $this->urlGenerator->generate('openpgp', [], UrlGeneratorInterface::ABSOLUTE_PATH);
        $parameters = [
            '%email%' => $key->getEmail(),
            '%expiration_date%' => $key->getExpiresAt()?->format('Y-m-d') ?? '',
            '%openpgp_url%' => $openpgpUrl,
            '%project_name%' => $this->settingsService->get('project_name'),
        ];

        $bodies = array_map(
            fn(string $locale): string => trim($this->translator->trans('key-expired-body', $parameters, null, $locale)),
            self::LOCALES,
        );

        return implode("\n\n--\n\n", $bodies) . "\n";
    }

    private function buildSubject(OpenPgpKey $key): string
    {
        $parameters = ['%email%' => $key->getEmail()];

        return implode(' / ', array_map(
            fn(string $locale): string => $this->translator->trans('key-expired-subject', $parameters, null, $locale),
            self::LOCALES,
        ));
    }
}
