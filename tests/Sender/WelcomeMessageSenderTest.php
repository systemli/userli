<?php

declare(strict_types=1);

namespace App\Tests\Sender;

use App\Builder\WelcomeMessageBuilder;
use App\Entity\Domain;
use App\Entity\User;
use App\Handler\MailHandler;
use App\Repository\DomainRepository;
use App\Sender\WelcomeMessageSender;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class WelcomeMessageSenderTest extends TestCase
{
    private function createManager(?Domain $domain = null): EntityManagerInterface
    {
        $repository = $this->createStub(DomainRepository::class);
        $repository->method('getDefaultDomain')->willReturn($domain);

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);

        return $manager;
    }

    private function createDomain(string $name = 'example.org'): Domain
    {
        $domain = new Domain();
        $domain->setName($name);

        return $domain;
    }

    public function testSendCallsMailHandler(): void
    {
        $handler = $this->createMock(MailHandler::class);
        $handler->expects($this->once())
            ->method('send')
            ->with('user@example.org', 'Welcome body', 'Welcome subject');

        $builder = $this->createStub(WelcomeMessageBuilder::class);
        $builder->method('buildBody')->willReturn('Welcome body');
        $builder->method('buildSubject')->willReturn('Welcome subject');

        $sender = new WelcomeMessageSender($handler, $builder, $this->createManager($this->createDomain()));
        $sender->send(new User('user@example.org'), 'en');
    }

    public function testSendPassesLocaleToBuilder(): void
    {
        $builder = $this->createMock(WelcomeMessageBuilder::class);
        $builder->expects($this->once())
            ->method('buildBody')
            ->with('de')
            ->willReturn('Willkommen');
        $builder->expects($this->once())
            ->method('buildSubject')
            ->with('de')
            ->willReturn('Willkommen Betreff');

        $handler = $this->createMock(MailHandler::class);
        $handler->expects($this->once())->method('send');

        $sender = new WelcomeMessageSender($handler, $builder, $this->createManager($this->createDomain()));
        $sender->send(new User('user@example.org'), 'de');
    }

    public function testSendWithNoDomainStillSends(): void
    {
        $handler = $this->createMock(MailHandler::class);
        $handler->expects($this->once())->method('send');

        $builder = $this->createStub(WelcomeMessageBuilder::class);
        $builder->method('buildBody')->willReturn('Welcome body');
        $builder->method('buildSubject')->willReturn('Welcome subject');

        $sender = new WelcomeMessageSender($handler, $builder, $this->createManager(null));
        $sender->send(new User('user@example.org'), 'en');
    }
}
