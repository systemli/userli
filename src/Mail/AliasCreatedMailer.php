<?php

declare(strict_types=1);

namespace App\Mail;

use App\Entity\Alias;
use App\Entity\User;
use App\Handler\MailHandler;
use App\Service\SettingsService;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AliasCreatedMailer
{
    public function __construct(
        private MailHandler $handler,
        private TranslatorInterface $translator,
        private SettingsService $settingsService,
    ) {
    }

    public function send(User $user, Alias $alias, string $locale): void
    {
        $email = $user->getEmail();
        $body = $this->buildBody($locale, $email, $alias->getSource());
        $subject = $this->buildSubject($locale, $email);
        $this->handler->send($email, $body, $subject);
    }

    private function buildBody(string $locale, string $email, string $alias): string
    {
        return $this->translator->trans(
            'mail.alias-created-body',
            [
                '%app_url%' => $this->settingsService->get('app_url'),
                '%project_name%' => $this->settingsService->get('project_name'),
                '%email%' => $email,
                '%alias%' => $alias,
            ],
            null,
            $locale
        );
    }

    private function buildSubject(string $locale, string $email): string
    {
        return $this->translator->trans(
            'mail.alias-created-subject',
            ['%email%' => $email],
            null,
            $locale
        );
    }
}
