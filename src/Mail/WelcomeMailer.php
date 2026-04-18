<?php

declare(strict_types=1);

namespace App\Mail;

use App\Entity\User;
use App\Handler\MailHandler;
use App\Service\SettingsService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class WelcomeMailer
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
        $body = $this->buildBody($locale);
        $subject = $this->buildSubject($locale);
        $this->handler->send($email, $body, $subject);
    }

    private function buildBody(string $locale): string
    {
        $appUrl = rtrim((string) $this->settingsService->get('app_url'), '/');

        return $this->translator->trans(
            'mail.welcome-body',
            [
                '%project_name%' => $this->settingsService->get('project_name'),
                '%voucher_url%' => $appUrl.$this->urlGenerator->generate('vouchers', [], UrlGeneratorInterface::ABSOLUTE_PATH),
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
