<?php

namespace App\Handler;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class UserRegistrationInfoHandler.
 */
class UserRegistrationInfoHandler
{
    private EntityManagerInterface $manager;
    private MailHandler $handler;
    private \Twig_Environment $twig;
    private string $to;

    /**
     * UserRegistrationInfoHandler constructor.
     *
     * @param string $to
     */
    public function __construct(EntityManagerInterface $manager, MailHandler $handler, \Twig_Environment $twig, $to)
    {
        $this->manager = $manager;
        $this->handler = $handler;
        $this->twig = $twig;
        $this->to = $to;
    }

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function sendReport(string $from = '-7 days'): void
    {
        $users = $this->manager->getRepository(User::class)->findUsersSince((new \DateTime())->modify($from));
        $message = $this->twig->render('Email/weekly_report.twig', ['users' => $users]);
        $this->handler->send($this->to, $message, 'Weekly Report: Registered E-mail Accounts');
    }
}
