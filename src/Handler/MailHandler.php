<?php

namespace App\Handler;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailHandler
{
    private MailerInterface $mailer;
    private string $from;
    private string $name;

    public function __construct(MailerInterface $mailer, string $from, string $name)
    {
        $this->mailer = $mailer;
        $this->from = $from;
        $this->name = $name;
    }

    /**
     * @param string $email
     * @param string $plain
     * @param string $subject
     * @param array  $params
     */
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
