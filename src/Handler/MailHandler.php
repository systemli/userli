<?php

namespace App\Handler;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailHandler
{
    public function __construct(private readonly MailerInterface $mailer, private readonly string $from, private readonly string $name)
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
