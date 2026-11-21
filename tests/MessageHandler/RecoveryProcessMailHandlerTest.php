<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\User;
use App\Mail\RecoveryProcessMailer;
use App\Message\RecoveryProcessMail;
use App\MessageHandler\RecoveryProcessMailHandler;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class RecoveryProcessMailHandlerTest extends TestCase
{
    public function testInvokeSendsMailForRunningRecovery(): void
    {
        $user = new User('user@example.org');
        $user->setRecoveryStartTime(new DateTimeImmutable());

        $mailer = $this->createMock(RecoveryProcessMailer::class);
        $mailer->expects($this->once())->method('send')->with($user, 'de');

        $handler = new RecoveryProcessMailHandler($this->createEntityManager($user), $mailer);
        $handler(new RecoveryProcessMail('user@example.org', 'de'));
    }

    public function testInvokeDoesNothingWhenUserNotFound(): void
    {
        $mailer = $this->createMock(RecoveryProcessMailer::class);
        $mailer->expects($this->never())->method('send');

        $handler = new RecoveryProcessMailHandler($this->createEntityManager(null), $mailer);
        $handler(new RecoveryProcessMail('missing@example.org', 'de'));
    }

    public function testInvokeDoesNothingWhenRecoveryIsNoLongerRunning(): void
    {
        // Cancelled before the worker picked up the message
        $mailer = $this->createMock(RecoveryProcessMailer::class);
        $mailer->expects($this->never())->method('send');

        $handler = new RecoveryProcessMailHandler($this->createEntityManager(new User('user@example.org')), $mailer);
        $handler(new RecoveryProcessMail('user@example.org', 'de'));
    }

    private function createEntityManager(?User $user): EntityManagerInterface
    {
        $repo = $this->createStub(EntityRepository::class);
        $repo->method('findOneBy')->willReturn($user);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        return $em;
    }
}
