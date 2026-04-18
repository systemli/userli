<?php

declare(strict_types=1);

namespace App\Mail;

use App\Entity\Alias;
use App\Entity\User;
use App\Handler\MailHandler;
use App\Service\SettingsService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AliasCreatedMailer
{
    public function __construct(
        private MailHandler $handler,
        private TranslatorInterface $translator,
        private SettingsService $settingsService,
        private UrlGeneratorInterface $urlGenerator,
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
                '%project_name%' => $this->settingsService->get('project_name'),
                '%email%' => $email,
                '%alias%' => $alias,
                '%alias_url%' => $this->urlGenerator->generate('aliases', [], UrlGeneratorInterface::ABSOLUTE_URL),
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
