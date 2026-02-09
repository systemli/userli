<?php

declare(strict_types=1);

namespace App\Tests\Sender;

use App\Builder\AliasCreatedMessageBuilder;
use App\Entity\Alias;
use App\Entity\User;
use App\Handler\MailHandler;
use App\Sender\AliasCreatedMessageSender;
use PHPUnit\Framework\TestCase;

class AliasCreatedMessageSenderTest extends TestCase
{
    public function testSendCallsBuilderAndMailHandler(): void
    {
        $user = new User('user@example.org');

        $alias = new Alias();
        $alias->setSource('alias@example.org');

        $builder = $this->createMock(AliasCreatedMessageBuilder::class);
        $builder->expects($this->once())
            ->method('buildBody')
            ->with('en', 'user@example.org', 'alias@example.org')
            ->willReturn('Alias body');
        $builder->expects($this->once())
            ->method('buildSubject')
            ->with('en', 'user@example.org')
            ->willReturn('Alias subject');

        $handler = $this->createMock(MailHandler::class);
        $handler->expects($this->once())
            ->method('send')
            ->with('user@example.org', 'Alias body', 'Alias subject');

        $sender = new AliasCreatedMessageSender($handler, $builder);
        $sender->send($user, $alias, 'en');
    }

    public function testSendPassesLocaleToBuilder(): void
    {
        $user = new User('user@example.org');
        $alias = new Alias();
        $alias->setSource('alias@example.org');

        $builder = $this->createMock(AliasCreatedMessageBuilder::class);
        $builder->expects($this->once())
            ->method('buildBody')
            ->with('de')
            ->willReturn('Alias Nachricht');
        $builder->expects($this->once())
            ->method('buildSubject')
            ->with('de')
            ->willReturn('Alias Betreff');

        $handler = $this->createMock(MailHandler::class);
        $handler->expects($this->once())->method('send');

        $sender = new AliasCreatedMessageSender($handler, $builder);
        $sender->send($user, $alias, 'de');
    }
}
