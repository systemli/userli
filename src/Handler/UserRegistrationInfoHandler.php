<?php

namespace App\Handler;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use DateTime;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class UserRegistrationInfoHandler.
 */
class UserRegistrationInfoHandler
{
    /**
     * UserRegistrationInfoHandler constructor.
     */
    public function __construct(private readonly EntityManagerInterface $manager, private readonly MailHandler $handler, private readonly Environment $twig, private readonly string $to)
    {
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError|\DateMalformedStringException
     */
    public function sendReport(string $from = '-7 days'): void
    {
        $users = $this->manager->getRepository(User::class)->findUsersSince((new DateTime())->modify($from));
        $message = $this->twig->render('Email/weekly_report.twig', ['users' => $users]);
        $this->handler->send($this->to, $message, 'Weekly Report: Registered E-mail Accounts');
    }
}
