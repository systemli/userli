<?php

namespace App\Handler;

use Twig\Environment;
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
    public function __construct(private readonly MailHandler $handler, private readonly Environment $twig, private readonly string $to)
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
