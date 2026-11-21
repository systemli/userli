<?php

declare(strict_types=1);

namespace App\Mail;

use App\Entity\User;
use App\Handler\MailHandler;
use App\Handler\RecoveryHandler;
use App\Service\RecoveryCancelLinkService;
use App\Service\SettingsService;
use IntlDateFormatter;
use LogicException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class RecoveryProcessMailer
{
    public function __construct(
        private MailHandler $handler,
        private TranslatorInterface $translator,
        private SettingsService $settingsService,
        private UrlGeneratorInterface $urlGenerator,
        private RecoveryCancelLinkService $cancelLinkService,
    ) {
    }

    public function send(User $user, string $locale): void
    {
        $recoveryStartTime = $user->getRecoveryStartTime();
        if (null === $recoveryStartTime) {
            throw new LogicException('Recovery process has not been started');
        }

        $email = $user->getEmail();
        $formatter = IntlDateFormatter::create($locale, IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);
        $time = $formatter->format(RecoveryHandler::getActiveTime($recoveryStartTime));

        $body = $this->buildBody($locale, $email, $time, $this->cancelLinkService->generatePath($user));
        $subject = $this->buildSubject($locale, $email);
        $this->handler->send($email, $body, $subject);
    }

    private function buildBody(string $locale, string $email, string $time, string $cancelPath): string
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
                '%hours%' => RecoveryHandler::PROCESS_DELAY_HOURS,
                '%recovery_url%' => $appUrl.$this->urlGenerator->generate('recovery', [], UrlGeneratorInterface::ABSOLUTE_PATH),
                '%recovery_token_url%' => $appUrl.$this->urlGenerator->generate('account_recovery_token', [], UrlGeneratorInterface::ABSOLUTE_PATH),
                '%cancel_url%' => $appUrl.$cancelPath,
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
