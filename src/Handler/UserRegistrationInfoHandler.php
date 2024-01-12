<?php

namespace App\Handler;

use Twig_Environment;
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
    public function __construct(private EntityManagerInterface $manager, private MailHandler $handler, private Twig_Environment $twig, private string $to)
    {
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function sendReport(string $from = '-7 days'): void
    {
        $users = $this->manager->getRepository(User::class)->findUsersSince((new DateTime())->modify($from));
        $message = $this->twig->render('Email/weekly_report.twig', ['users' => $users]);
        $this->handler->send($this->to, $message, 'Weekly Report: Registered E-mail Accounts');
    }
}
