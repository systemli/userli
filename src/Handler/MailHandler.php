<?php

namespace App\Handler;

use Swift_Mailer;
use Swift_Message;

class MailHandler
{
    /**
     * @var Swift_Mailer
     */
    private $mailer;

    /**
     * @var string
     */
    private $from;

    /**
     * @var string
     */
    private $name;

    /**
     * Constructor.
     *
     * @param string $from
     * @param string $name
     */
    public function __construct(Swift_Mailer $mailer, $from, $name)
    {
        $this->mailer = $mailer;
        $this->from = $from;
        $this->name = $name;
    }

    /**
     * @param string $email
     * @param $plain
     * @param $subject
     */
    public function send($email, $plain, $subject, array $params = [])
    {
        $message = (new Swift_Message($subject, $plain, 'text/plain'))
            ->setTo($email)
            ->setFrom($this->from, $this->name);

        if (isset($params['bcc'])) {
            $message->setBcc($params['bcc']);
        }

        if (isset($params['html'])) {
            $message->addPart($params['html'], 'text/html');
        }

        $this->mailer->send($message);
    }
}
