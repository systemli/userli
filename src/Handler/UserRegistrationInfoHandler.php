<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\SettingsService;
use Twig\Environment;
use DateTime;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final readonly class UserRegistrationInfoHandler
{
    public function __construct(
        private EntityManagerInterface $manager,
        private MailHandler            $handler,
        private Environment            $twig,
        private SettingsService        $settingsService,
    ) {
    }

    public function sendReport(string $from = '-7 days'): void
    {
        $users = $this->manager->getRepository(User::class)->findUsersSince((new DateTime())->modify($from));
        $message = $this->twig->render('Email/weekly_report.twig', ['users' => $users]);
        $email = $this->settingsService->get('email_notification_address');

        $this->handler->send($email, $message, 'Weekly Report: Registered E-mail Accounts');
    }
}
