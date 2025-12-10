<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Entity\User;
use App\Message\WelcomeMail;
use App\MessageHandler\WelcomeMailHandler;
use App\Sender\WelcomeMessageSender;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;

class WelcomeMailHandlerTest extends TestCase
{
    public function testInvokeSendsMailWhenUserExists(): void
    {
        $email = 'user@example.test';
        $locale = 'de';

        $user = new User($email);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($user);
        $repo->method('getClassName')->willReturn(User::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(User::class)->willReturn($repo);

        $sender = $this->createMock(WelcomeMessageSender::class);
        $sender->expects($this->once())
            ->method('send')
            ->with($user, $locale);

        $handler = new WelcomeMailHandler($em, $sender);
        $handler(new WelcomeMail($email, $locale));
    }

    public function testInvokeDoesNothingWhenUserNotFound(): void
    {
        $email = 'missing@example.test';

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn(null);
        $repo->method('getClassName')->willReturn(User::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(User::class)->willReturn($repo);

        $sender = $this->createMock(WelcomeMessageSender::class);
        $sender->expects($this->never())->method('send');

        $handler = new WelcomeMailHandler($em, $sender);
        $handler(new WelcomeMail($email, 'en'));
    }
}
