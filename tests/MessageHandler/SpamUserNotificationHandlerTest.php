<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\User;
use App\Enum\Roles;
use App\Mail\SpamUserMailer;
use App\Message\SpamUserNotification;
use App\MessageHandler\SpamUserNotificationHandler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SpamUserNotificationHandlerTest extends TestCase
{
    public function testInvokeSendsMailForSpamUser(): void
    {
        $user = new User('user@example.test');
        $user->setRoles([Roles::SPAM]);

        $mailer = $this->createMock(SpamUserMailer::class);
        $mailer->expects($this->once())->method('send')->with($user);

        $this->createHandler($user, $mailer)(new SpamUserNotification(1));
    }

    public function testInvokeSkipsWhenRoleWasRemoved(): void
    {
        $user = new User('user@example.test');
        $user->setRoles([Roles::USER]);

        $mailer = $this->createMock(SpamUserMailer::class);
        $mailer->expects($this->never())->method('send');

        $this->createHandler($user, $mailer)(new SpamUserNotification(1));
    }

    public function testInvokeSkipsDeletedUser(): void
    {
        $user = new User('user@example.test');
        $user->setRoles([Roles::SPAM]);
        $user->setDeleted(true);

        $mailer = $this->createMock(SpamUserMailer::class);
        $mailer->expects($this->never())->method('send');

        $this->createHandler($user, $mailer)(new SpamUserNotification(1));
    }

    public function testInvokeLogsWhenUserNotFound(): void
    {
        $mailer = $this->createMock(SpamUserMailer::class);
        $mailer->expects($this->never())->method('send');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');

        $this->createHandler(null, $mailer, $logger)(new SpamUserNotification(1));
    }

    private function createHandler(?User $user, SpamUserMailer $mailer, ?LoggerInterface $logger = null): SpamUserNotificationHandler
    {
        $repo = $this->createStub(EntityRepository::class);
        $repo->method('find')->willReturn($user);
        $repo->method('getClassName')->willReturn(User::class);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        return new SpamUserNotificationHandler($em, $mailer, $logger ?? $this->createStub(LoggerInterface::class));
    }
}
