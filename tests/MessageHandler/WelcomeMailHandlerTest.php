<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\User;
use App\Mail\WelcomeMailer;
use App\Message\WelcomeMail;
use App\MessageHandler\WelcomeMailHandler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class WelcomeMailHandlerTest extends TestCase
{
    public function testInvokeSendsMailWhenUserExists(): void
    {
        $email = 'user@example.test';
        $locale = 'de';

        $user = new User($email);

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($user);
        $repo->method('getClassName')->willReturn(User::class);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $mailer = $this->createMock(WelcomeMailer::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($user, $locale);

        $handler = new WelcomeMailHandler($em, $mailer);
        $handler(new WelcomeMail($email, $locale));
    }

    public function testInvokeDoesNothingWhenUserNotFound(): void
    {
        $email = 'missing@example.test';

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn(null);
        $repo->method('getClassName')->willReturn(User::class);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $mailer = $this->createMock(WelcomeMailer::class);
        $mailer->expects($this->never())->method('send');

        $handler = new WelcomeMailHandler($em, $mailer);
        $handler(new WelcomeMail($email, 'en'));
    }
}
