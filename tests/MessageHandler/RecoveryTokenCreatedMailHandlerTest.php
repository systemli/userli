<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\User;
use App\Mail\RecoveryTokenCreatedMailer;
use App\Message\RecoveryTokenCreatedMail;
use App\MessageHandler\RecoveryTokenCreatedMailHandler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class RecoveryTokenCreatedMailHandlerTest extends TestCase
{
    public function testInvokeSendsMailWhenUserExists(): void
    {
        $user = new User('user@example.org');

        $mailer = $this->createMock(RecoveryTokenCreatedMailer::class);
        $mailer->expects($this->once())->method('send')->with($user, 'de');

        $handler = new RecoveryTokenCreatedMailHandler($this->createEntityManager($user), $mailer);
        $handler(new RecoveryTokenCreatedMail('user@example.org', 'de'));
    }

    public function testInvokeDoesNothingWhenUserNotFound(): void
    {
        $mailer = $this->createMock(RecoveryTokenCreatedMailer::class);
        $mailer->expects($this->never())->method('send');

        $handler = new RecoveryTokenCreatedMailHandler($this->createEntityManager(null), $mailer);
        $handler(new RecoveryTokenCreatedMail('missing@example.org', 'de'));
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
