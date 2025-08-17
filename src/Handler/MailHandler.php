<?php

declare(strict_types=1);

namespace App\Handler;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

readonly class MailHandler
{
    public function __construct(private MailerInterface $mailer, private string $from, private string $name)
    {
    }

    public function send(string $email, string $plain, string $subject, array $params = []): void
    {
        $message = (new Email())
            ->from(new Address($this->from, $this->name))
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
