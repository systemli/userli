<?php

namespace App\Handler;

/**
 * Class SuspiciousChildrenHandler.
 */
class SuspiciousChildrenHandler
{
    /**
     * @var MailHandler
     */
    private $handler;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var string
     */
    private $to;

    /**
     * SuspiciousChildrenHandler constructor.
     *
     * @param string $to
     */
    public function __construct(MailHandler $handler, \Twig_Environment $twig, $to)
    {
        $this->handler = $handler;
        $this->twig = $twig;
        $this->to = $to;
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
