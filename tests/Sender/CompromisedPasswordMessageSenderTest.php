<?php

declare(strict_types=1);

namespace App\Tests\Sender;

use App\Builder\CompromisedPasswordMessageBuilder;
use App\Entity\User;
use App\Handler\MailHandler;
use App\Sender\CompromisedPasswordMessageSender;
use PHPUnit\Framework\TestCase;

class CompromisedPasswordMessageSenderTest extends TestCase
{
    private MailHandler $mailHandler;
    private CompromisedPasswordMessageBuilder $builder;
    private CompromisedPasswordMessageSender $sender;

    protected function setUp(): void
    {
        $this->mailHandler = $this->createMock(MailHandler::class);
        $this->builder = $this->createMock(CompromisedPasswordMessageBuilder::class);
        $this->sender = new CompromisedPasswordMessageSender(
            $this->mailHandler,
            $this->builder
        );
    }

    public function testSendSuccessfully(): void
    {
        $user = new User();
        $user->setEmail('test@example.org');
        $locale = 'en';

        $expectedBody = 'Hello test@example.org, your password may be compromised...';
        $expectedSubject = 'Security Alert - Your password may be compromised';

        $this->builder->expects($this->once())
            ->method('buildBody')
            ->with($locale, 'test@example.org')
            ->willReturn($expectedBody);

        $this->builder->expects($this->once())
            ->method('buildSubject')
            ->with($locale)
            ->willReturn($expectedSubject);

        $this->mailHandler->expects($this->once())
            ->method('send')
            ->with('test@example.org', $expectedBody, $expectedSubject);

        $this->sender->send($user, $locale);
    }

    public function testSendWithDifferentLocale(): void
    {
        $user = new User();
        $user->setEmail('benutzer@example.org');
        $locale = 'de';

        $expectedBody = 'Hallo benutzer@example.org, Ihr Passwort könnte kompromittiert sein...';
        $expectedSubject = 'Sicherheitswarnung - Ihr Passwort könnte kompromittiert sein';

        $this->builder->expects($this->once())
            ->method('buildBody')
            ->with($locale, 'benutzer@example.org')
            ->willReturn($expectedBody);

        $this->builder->expects($this->once())
            ->method('buildSubject')
            ->with($locale)
            ->willReturn($expectedSubject);

        $this->mailHandler->expects($this->once())
            ->method('send')
            ->with('benutzer@example.org', $expectedBody, $expectedSubject);

        $this->sender->send($user, $locale);
    }

    public function testSendCallsBuilderAndHandlerInCorrectOrder(): void
    {
        $user = new User();
        $user->setEmail('order@example.org');
        $locale = 'en';

        // Create a sequence to verify order of calls
        $callOrder = [];

        $this->builder->expects($this->once())
            ->method('buildBody')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'buildBody';
                return 'body';
            });

        $this->builder->expects($this->once())
            ->method('buildSubject')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'buildSubject';
                return 'subject';
            });

        $this->mailHandler->expects($this->once())
            ->method('send')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'send';
            });

        $this->sender->send($user, $locale);

        $this->assertEquals(['buildBody', 'buildSubject', 'send'], $callOrder);
    }
}
