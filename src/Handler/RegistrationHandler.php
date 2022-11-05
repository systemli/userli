<?php

namespace App\Handler;

use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Event\Events;
use App\Event\UserEvent;
use App\Form\Model\Registration;
use App\Guesser\DomainGuesser;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RegistrationHandler
{
    private EntityManagerInterface $manager;
    private DomainGuesser $domainGuesser;
    private EventDispatcherInterface $eventDispatcher;
    private PasswordUpdater $passwordUpdater;
    private MailCryptKeyHandler $mailCryptKeyHandler;
    private RecoveryTokenHandler $recoveryTokenHandler;
    private bool $registrationOpen;
    private $mailCrypt;

    /**
     * Constructor.
     */
    public function __construct(
        EntityManagerInterface $manager,
        DomainGuesser $domainGuesser,
        EventDispatcherInterface $eventDispatcher,
        PasswordUpdater $passwordUpdater,
        MailCryptKeyHandler $mailCryptKeyHandler,
        RecoveryTokenHandler $recoveryTokenHandler,
        bool $registrationOpen,
        bool $mailCrypt
    ) {
        $this->manager = $manager;
        $this->domainGuesser = $domainGuesser;
        $this->eventDispatcher = $eventDispatcher;
        $this->passwordUpdater = $passwordUpdater;
        $this->mailCryptKeyHandler = $mailCryptKeyHandler;
        $this->recoveryTokenHandler = $recoveryTokenHandler;
        $this->registrationOpen = $registrationOpen;
        $this->mailCrypt = $mailCrypt;
    }

    /**
     * @throws \Exception
     */
    public function handle(Registration $registration): void
    {
        if (!$this->isRegistrationOpen()) {
            throw new \Exception('The Registration is closed!');
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

        $this->eventDispatcher->dispatch(new UserEvent($user), Events::MAIL_ACCOUNT_CREATED);
    }

    public function isRegistrationOpen(): bool
    {
        return $this->registrationOpen;
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

        if (null !== $voucher = $this->manager->getRepository(Voucher::class)->findByCode($registration->getVoucher())) {
            $voucher->setRedeemedTime(new \DateTime());

            $user->setInvitationVoucher($voucher);

            $this->manager->flush();

            return $user;
        }

        return $user;
    }
}
