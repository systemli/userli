<?php

namespace App\Handler;

/**
 * Class SuspiciousChildrenHandler.
 */
class SuspiciousChildrenHandler
{
    /**
     * SuspiciousChildrenHandler constructor.
     */
    public function __construct(private MailHandler $handler, private \Twig_Environment $twig, private string $to)
    {
    }

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function sendReport(array $suspiciousChildren): void
    {
        $message = $this->twig->render('Email/suspicious_children.twig', ['suspiciousChildren' => $suspiciousChildren]);
        $this->handler->send($this->to, $message, 'Suspicious users invited more users');
    }
}
