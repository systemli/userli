<?php

namespace App\Handler;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class UserRegistrationInfoHandler.
 */
class UserRegistrationInfoHandler
{
    /**
     * @var ObjectManager
     */
    private $manager;

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
     * UserRegistrationInfoHandler constructor.
     *
     * @param string $to
     */
    public function __construct(ObjectManager $manager, MailHandler $handler, \Twig_Environment $twig, $to)
    {
        $this->manager = $manager;
        $this->handler = $handler;
        $this->twig = $twig;
        $this->to = $to;
    }

    /**
     * @param string $from
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function sendReport($from = '-7 days')
    {
        $users = $this->manager->getRepository('App:User')->findUsersSince((new \DateTime())->modify($from));
        $message = $this->twig->render('Email/weekly_report.twig', ['users' => $users]);
        $this->handler->send($this->to, $message, 'Weekly Report: Registered E-Mail Accounts');
    }
}
