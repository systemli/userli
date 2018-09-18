<?php

namespace AppBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class SuspiciousChildrenHandler.
 */
class SuspiciousChildrenHandler
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
     * SuspiciousChildrenHandler constructor.
     *
     * @param ObjectManager     $manager
     * @param MailHandler       $handler
     * @param \Twig_Environment $twig
     * @param string            $to
     */
    public function __construct(ObjectManager $manager, MailHandler $handler, \Twig_Environment $twig, $to)
    {
        $this->manager = $manager;
        $this->handler = $handler;
        $this->twig = $twig;
        $this->to = $to;
    }

    /**
     * @param array $suspiciousChildren
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function sendReport($suspiciousChildren)
    {
        $message = $this->twig->render('@App/Email/suspicious_children.twig', ['suspiciousChildren' => $suspiciousChildren]);
        $this->handler->send($this->to, $message, 'Suspicious users invited more users');
    }
}
