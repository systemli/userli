<?php

namespace App\Handler;

use App\Entity\User;
use App\Enum\Roles;
use App\Event\Events;
use App\Event\UserEvent;
use App\Form\Model\Registration;
use App\Guesser\DomainGuesser;
use App\Helper\PasswordUpdater;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RegistrationHandler
{
    private const REGISTRATION_LIMIT = 9999;

    /**
     * @var ObjectManager
     */
    private $manager;
    /**
     * @var DomainGuesser
     */
    private $domainGuesser;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var PasswordUpdater
     */
    private $passwordUpdater;
    /**
     * @var MailCryptKeyHandler
     */
    private $mailCryptKeyHandler;
    /**
     * @var RecoveryTokenHandler
     */
    private $recoveryTokenHandler;
    /**
     * @var bool
     */
    private $hasSinaBox;
    /**
     * @var int
     */
    private $mailCrypt;

    /**
     * Constructor.
     */
    public function __construct(
        ObjectManager $manager,
        DomainGuesser $domainGuesser,
        EventDispatcherInterface $eventDispatcher,
        PasswordUpdater $passwordUpdater,
        MailCryptKeyHandler $mailCryptKeyHandler,
        RecoveryTokenHandler $recoveryTokenHandler,
        bool $hasSinaBox,
        bool $mailCrypt
    ) {
        $this->manager = $manager;
        $this->domainGuesser = $domainGuesser;
        $this->eventDispatcher = $eventDispatcher;
        $this->passwordUpdater = $passwordUpdater;
        $this->mailCryptKeyHandler = $mailCryptKeyHandler;
        $this->recoveryTokenHandler = $recoveryTokenHandler;
        $this->hasSinaBox = $hasSinaBox;
        $this->mailCrypt = $mailCrypt;
    }

    /**
     * @throws \Exception
     */
    public function handle(Registration $registration): void
    {
        if (!$this->canRegister()) {
            throw new \Exception('The Registration Limit reached!');
        }

        // Create user
        $user = $this->buildUser($registration);

        // Update password, generate MailCrypt keys, generate recovery token
        $this->passwordUpdater->updatePassword($user);
        $this->mailCryptKeyHandler->create($user);
        $this->recoveryTokenHandler->create($user);

        // Enable mailbox encryption
        if ($this->mailCrypt >= 2) {
            $user->setMailCrypt(true);
        }

        // Erase sensitive plaintext data from User object
        $user->eraseCredentials();
        $user->erasePlainMailCryptPrivateKey();

        $this->manager->persist($user);
        $this->manager->flush();

        $this->eventDispatcher->dispatch(Events::MAIL_ACCOUNT_CREATED, new UserEvent($user));
    }

    public function canRegister(): bool
    {
        $count = $this->manager->getRepository('App:User')->count([]);

        return !(!$this->hasSinaBox && $count > self::REGISTRATION_LIMIT);
    }

    private function buildUser(Registration $registration): User
    {
        $user = new User();
        $user->setEmail(strtolower($registration->getEmail()));
        $user->setPlainPassword($registration->getPlainPassword());
        $user->setRoles([Roles::USER]);

        if (null !== $domain = $this->domainGuesser->guess($registration->getEmail())) {
            $user->setDomain($domain);
        }

        if (null !== $voucher = $this->manager->getRepository('App:Voucher')->findByCode($registration->getVoucher())) {
            $voucher->setRedeemedTime(new \DateTime());

            $user->setInvitationVoucher($voucher);

            $this->manager->flush();

            return $user;
        }

        return $user;
    }
}
