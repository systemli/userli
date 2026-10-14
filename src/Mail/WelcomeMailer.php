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
        $body = $this->buildBody($user, $locale);
        $subject = $this->buildSubject($locale);
        $this->handler->send($email, $body, $subject);
    }

    private function buildBody(User $user, string $locale): string
    {
        $projectName = $this->settingsService->get('project_name');
        $appUrl = rtrim((string) $this->settingsService->get('app_url'), '/');
        $voucherUrl = $appUrl.$this->urlGenerator->generate('vouchers', [], UrlGeneratorInterface::ABSOLUTE_PATH);

        $quota = $user->getQuota();
        $quotaText = null === $quota
            ? $this->translator->trans('mail.welcome-quota-unlimited', [], null, $locale)
            : $this->translator->trans('mail.welcome-quota', ['%quota%' => $quota], null, $locale);

        $invitationText = '';
        $invitationSettings = $user->getDomain()?->getInvitationSettings();
        if (null !== $invitationSettings && $invitationSettings->isEnabled() && $invitationSettings->getLimit() > 0) {
            $invitationText = $this->translator->trans(
                'mail.welcome-invitations',
                [
                    '%project_name%' => $projectName,
                    '%voucher_url%' => $voucherUrl,
                    '%limit%' => $invitationSettings->getLimit(),
                    '%waitingPeriodDays%' => $invitationSettings->getWaitingPeriodDays(),
                ],
                null,
                $locale
            );
        }

        $body = $this->translator->trans(
            'mail.welcome-body',
            [
                '%project_name%' => $projectName,
                '%voucher_url%' => $voucherUrl,
                '%quota_text%' => trim($quotaText),
                '%invitation_text%' => trim($invitationText),
            ],
            null,
            $locale
        );

        // Collapse the blank lines left behind by an omitted invitation paragraph
        return preg_replace("/\n{3,}/", "\n\n", $body) ?? $body;
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
