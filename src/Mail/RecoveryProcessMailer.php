<?php

declare(strict_types=1);

namespace App\Mail;

use App\Entity\User;
use App\Handler\MailHandler;
use App\Service\SettingsService;
use DateInterval;
use IntlDateFormatter;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class RecoveryProcessMailer
{
    /**
     * Expiry window for the one-click recovery-token regeneration link.
     * Matches RecoveryHandler::PROCESS_EXPIRE so the link stays usable
     * as long as the recovery process itself is still valid.
     */
    private const string REGENERATE_LINK_TTL = 'P30D';

    public function __construct(
        private MailHandler $handler,
        private TranslatorInterface $translator,
        private SettingsService $settingsService,
        private UrlGeneratorInterface $urlGenerator,
        private UriSigner $uriSigner,
    ) {
    }

    public function send(User $user, string $locale): void
    {
        $email = $user->getEmail();
        $formatter = IntlDateFormatter::create($locale, IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);
        $time = $formatter->format($user->getRecoveryStartTime()->add(new DateInterval('P2D')));

        $body = $this->buildBody($user, $locale, $email, $time);
        $subject = $this->buildSubject($locale, $email);
        $this->handler->send($email, $body, $subject);
    }

    private function buildBody(User $user, string $locale, string $email, string $time): string
    {
        // Host from the admin-editable `app_url` setting, path from the router. This keeps
        // a single source of truth for the host and works in background workers where no
        // request context is available.
        $appUrl = rtrim((string) $this->settingsService->get('app_url'), '/');

        return $this->translator->trans(
            'mail.recovery-body',
            [
                '%project_name%' => $this->settingsService->get('project_name'),
                '%email%' => $email,
                '%time%' => $time,
                '%recovery_url%' => $appUrl.$this->urlGenerator->generate('recovery', [], UrlGeneratorInterface::ABSOLUTE_PATH),
                '%recovery_token_url%' => $appUrl.$this->buildSignedRegeneratePath($user),
            ],
            null,
            $locale
        );
    }

    private function buildSignedRegeneratePath(User $user): string
    {
        $path = $this->urlGenerator->generate(
            'recovery_token_regenerate',
            ['user' => $user->getId()],
            UrlGeneratorInterface::ABSOLUTE_PATH,
        );

        return $this->uriSigner->sign($path, new DateInterval(self::REGENERATE_LINK_TTL));
    }

    private function buildSubject(string $locale, string $email): string
    {
        return $this->translator->trans(
            'mail.recovery-subject',
            ['%email%' => $email],
            null,
            $locale
        );
    }
}
