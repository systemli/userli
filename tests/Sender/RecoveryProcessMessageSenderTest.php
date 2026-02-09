<?php

declare(strict_types=1);

namespace App\Tests\Sender;

use App\Builder\RecoveryProcessMessageBuilder;
use App\Entity\User;
use App\Handler\MailHandler;
use App\Sender\RecoveryProcessMessageSender;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class RecoveryProcessMessageSenderTest extends TestCase
{
    public function testSendCallsBuilderAndMailHandler(): void
    {
        $user = new User('user@example.org');
        $user->setRecoveryStartTime(new DateTimeImmutable('2026-01-15 10:00:00'));

        $builder = $this->createMock(RecoveryProcessMessageBuilder::class);
        $builder->expects($this->once())
            ->method('buildBody')
            ->with('en', 'user@example.org', $this->isType('string'))
            ->willReturn('Recovery body');
        $builder->expects($this->once())
            ->method('buildSubject')
            ->with('en', 'user@example.org')
            ->willReturn('Recovery subject');

        $handler = $this->createMock(MailHandler::class);
        $handler->expects($this->once())
            ->method('send')
            ->with('user@example.org', 'Recovery body', 'Recovery subject');

        $sender = new RecoveryProcessMessageSender($handler, $builder);
        $sender->send($user, 'en');
    }

    public function testSendPassesLocaleToBuilder(): void
    {
        $user = new User('user@example.org');
        $user->setRecoveryStartTime(new DateTimeImmutable('2026-01-15 10:00:00'));

        $builder = $this->createMock(RecoveryProcessMessageBuilder::class);
        $builder->expects($this->once())
            ->method('buildBody')
            ->with('de', 'user@example.org', $this->isType('string'))
            ->willReturn('Wiederherstellung');
        $builder->expects($this->once())
            ->method('buildSubject')
            ->with('de', 'user@example.org')
            ->willReturn('Betreff');

        $handler = $this->createMock(MailHandler::class);
        $handler->expects($this->once())->method('send');

        $sender = new RecoveryProcessMessageSender($handler, $builder);
        $sender->send($user, 'de');
    }
}
