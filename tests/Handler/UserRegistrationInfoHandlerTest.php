<?php

declare(strict_types=1);

namespace App\Tests\Handler;

use App\Entity\User;
use App\Handler\MailHandler;
use App\Handler\UserRegistrationInfoHandler;
use App\Repository\UserRepository;
use App\Service\SettingsService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class UserRegistrationInfoHandlerTest extends TestCase
{
    private UserRegistrationInfoHandler $handler;
    private MockObject $entityManager;
    private MockObject $mailHandler;
    private MockObject $twig;
    private MockObject $settingsService;
    private MockObject $userRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->mailHandler = $this->createMock(MailHandler::class);
        $this->twig = $this->createMock(Environment::class);
        $this->settingsService = $this->createMock(SettingsService::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->entityManager->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        $this->handler = new UserRegistrationInfoHandler(
            $this->entityManager,
            $this->mailHandler,
            $this->twig,
            $this->settingsService
        );
    }

    public function testSendReportWithDefaultTimeframe(): void
    {
        $users = [
            $this->createUserMock('user1@example.com'),
            $this->createUserMock('user2@example.com'),
        ];
        $renderedMessage = 'Weekly report: 2 new users registered';
        $notificationEmail = 'admin@example.com';
        $expectedSubject = 'Weekly Report: Registered E-mail Accounts';

        $this->userRepository->expects(self::once())
            ->method('findUsersSince')
            ->with(self::callback(static function (DateTime $date) {
                // Verify that the date is approximately 7 days ago
                $expectedDate = (new DateTime())->modify('-7 days');
                $diff = abs($date->getTimestamp() - $expectedDate->getTimestamp());

                return $diff < 5; // Allow 5 seconds tolerance
            }))
            ->willReturn($users);

        $this->twig->expects(self::once())
            ->method('render')
            ->with('Email/weekly_report.twig', ['users' => $users])
            ->willReturn($renderedMessage);

        $this->settingsService->expects(self::once())
            ->method('get')
            ->with('email_notification_address')
            ->willReturn($notificationEmail);

        $this->mailHandler->expects(self::once())
            ->method('send')
            ->with($notificationEmail, $renderedMessage, $expectedSubject);

        $this->handler->sendReport();
    }

    public function testSendReportWithCustomTimeframe(): void
    {
        $users = [
            $this->createUserMock('newuser@example.org'),
        ];
        $renderedMessage = 'Monthly report: 1 new user registered';
        $notificationEmail = 'reports@example.org';
        $expectedSubject = 'Weekly Report: Registered E-mail Accounts';
        $customTimeframe = '-30 days';

        $this->userRepository->expects(self::once())
            ->method('findUsersSince')
            ->with(self::callback(static function (DateTime $date) use ($customTimeframe) {
                // Verify that the date is approximately 30 days ago
                $expectedDate = (new DateTime())->modify($customTimeframe);
                $diff = abs($date->getTimestamp() - $expectedDate->getTimestamp());

                return $diff < 5; // Allow 5 seconds tolerance
            }))
            ->willReturn($users);

        $this->twig->expects(self::once())
            ->method('render')
            ->with('Email/weekly_report.twig', ['users' => $users])
            ->willReturn($renderedMessage);

        $this->settingsService->expects(self::once())
            ->method('get')
            ->with('email_notification_address')
            ->willReturn($notificationEmail);

        $this->mailHandler->expects(self::once())
            ->method('send')
            ->with($notificationEmail, $renderedMessage, $expectedSubject);

        $this->handler->sendReport($customTimeframe);
    }

    public function testSendReportWithNoUsers(): void
    {
        $users = [];
        $renderedMessage = 'No new users registered in the selected timeframe.';
        $notificationEmail = 'notifications@example.com';
        $expectedSubject = 'Weekly Report: Registered E-mail Accounts';

        $this->userRepository->expects(self::once())
            ->method('findUsersSince')
            ->willReturn($users);

        $this->twig->expects(self::once())
            ->method('render')
            ->with('Email/weekly_report.twig', ['users' => $users])
            ->willReturn($renderedMessage);

        $this->settingsService->expects(self::once())
            ->method('get')
            ->with('email_notification_address')
            ->willReturn($notificationEmail);

        $this->mailHandler->expects(self::once())
            ->method('send')
            ->with($notificationEmail, $renderedMessage, $expectedSubject);

        $this->handler->sendReport();
    }

    private function createUserMock(string $email): MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn($email);

        return $user;
    }
}
