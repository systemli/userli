<?php

namespace App\Handler;

use Twig_Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
/**
 * Class SuspiciousChildrenHandler.
 */
class SuspiciousChildrenHandler
{
    /**
     * SuspiciousChildrenHandler constructor.
     */
    public function __construct(private MailHandler $handler, private Twig_Environment $twig, private string $to)
    {
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendReport(array $suspiciousChildren): void
    {
        $message = $this->twig->render('Email/suspicious_children.twig', ['suspiciousChildren' => $suspiciousChildren]);
        $this->handler->send($this->to, $message, 'Suspicious users invited more users');
    }
}
