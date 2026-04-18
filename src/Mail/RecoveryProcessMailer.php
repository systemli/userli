<?php

declare(strict_types=1);

namespace App\Mail;

use App\Entity\User;
use App\Handler\MailHandler;
use App\Service\SettingsService;
use DateInterval;
use IntlDateFormatter;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class RecoveryProcessMailer
{
    public function __construct(
        private MailHandler $handler,
        private TranslatorInterface $translator,
        private SettingsService $settingsService,
    ) {
    }

    public function send(User $user, string $locale): void
    {
        $email = $user->getEmail();
        $formatter = IntlDateFormatter::create($locale, IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);
        $time = $formatter->format($user->getRecoveryStartTime()->add(new DateInterval('P2D')));

        $body = $this->buildBody($locale, $email, $time);
        $subject = $this->buildSubject($locale, $email);
        $this->handler->send($email, $body, $subject);
    }

    private function buildBody(string $locale, string $email, string $time): string
    {
        $appUrl = (string) $this->settingsService->get('app_url');

        return $this->translator->trans(
            'mail.recovery-body',
            [
                '%app_url%' => $appUrl,
                '%project_name%' => $this->settingsService->get('project_name'),
                '%email%' => $email,
                '%time%' => $time,
                '%reset_link%' => rtrim($appUrl, '/').'/account/recovery-token',
            ],
            null,
            $locale
        );
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
