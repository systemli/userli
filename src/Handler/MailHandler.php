<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\SettingsService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class MailHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private SettingsService $settingsService,
    ) {
    }

    public function send(string $email, string $plain, string $subject, array $params = []): void
    {
        $from = $this->settingsService->get('email_sender_address');
        $name = $this->settingsService->get('app_name');

        $message = (new Email())
            ->from(new Address($from, $name))
            ->to($email)
            ->subject($subject)
            ->text($plain);

        if (isset($params['bcc'])) {
            $message->bcc($params['bcc']);
        }

        if (isset($params['html'])) {
            $message->html($params['html']);
        }

        $this->mailer->send($message);
    }
}
