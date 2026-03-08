<?php

declare(strict_types=1);

namespace App\Mail;

use App\Entity\User;
use App\Handler\MailHandler;
use App\Service\SettingsService;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class WelcomeMailer
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
        $body = $this->buildBody($locale);
        $subject = $this->buildSubject($locale);
        $this->handler->send($email, $body, $subject);
    }

    private function buildBody(string $locale): string
    {
        return $this->translator->trans(
            'mail.welcome-body',
            [
                '%app_url%' => $this->settingsService->get('app_url'),
                '%project_name%' => $this->settingsService->get('project_name'),
            ],
            null,
            $locale
        );
    }

    private function buildSubject(string $locale): string
    {
        return $this->translator->trans(
            'mail.welcome-subject',
            ['%project_name%' => $this->settingsService->get('project_name')],
            null,
            $locale
        );
    }
}
