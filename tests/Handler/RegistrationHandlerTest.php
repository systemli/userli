<?php

namespace App\Tests\Handler;

use Exception;
use App\Form\Model\Registration;
use App\Guesser\DomainGuesser;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use App\Handler\RegistrationHandler;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RegistrationHandlerTest extends TestCase
{
    public function testHandleWithDisabledRegistration()
    {
        $manager = $this->getMockBuilder(EntityManagerInterface::class)->disableOriginalConstructor()->getMock();
        $domainGuesser = $this->getMockBuilder(DomainGuesser::class)->disableOriginalConstructor()->getMock();
        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->disableOriginalConstructor()->getMock();
        $passwordUpdater = $this->getMockBuilder(PasswordUpdater::class)->disableOriginalConstructor()->getMock();
        $mailCryptKeyHandler = $this->getMockBuilder(MailCryptKeyHandler::class)->disableOriginalConstructor()->getMock();
        $recoveryTokenHandler = $this->getMockBuilder(RecoveryTokenHandler::class)->disableOriginalConstructor()->getMock();

        $handler = new RegistrationHandler($manager, $domainGuesser, $eventDispatcher, $passwordUpdater, $mailCryptKeyHandler, $recoveryTokenHandler, false, 2);

        $this->expectException(Exception::class);
        $handler->handle(new Registration());
    }
}
