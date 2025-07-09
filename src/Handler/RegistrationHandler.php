<?php

namespace App\Handler;

use App\Event\UserCreatedEvent;
use Exception;
use DateTime;
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

readonly class RegistrationHandler
{
    /**
     * Constructor.
     */
    public function __construct(
        private EntityManagerInterface   $manager,
        private DomainGuesser            $domainGuesser,
        private EventDispatcherInterface $eventDispatcher,
        private UserPasswordUpdateHandler $userPasswordUpdateHandler,
        private RecoveryTokenHandler     $recoveryTokenHandler,
        private bool                     $registrationOpen,
    )
    {
    }

    /**
     * @throws Exception
     */
    public function handle(Registration $registration): void
    {
        if (!$this->isRegistrationOpen()) {
            throw new Exception('The Registration is closed!');
        }

        // Create user
        $user = $this->buildUser($registration);

        $this->userPasswordUpdateHandler->updatePassword($user, $registration->getPlainPassword());
        $this->recoveryTokenHandler->create($user);

        // We used to erase sensitive data here, but it's now done in RegistrationController
        // as we need to print the plainRecoveryToken beforehand

        $this->manager->persist($user);
        $this->manager->flush();

        $this->eventDispatcher->dispatch(new UserCreatedEvent($user));
        $this->eventDispatcher->dispatch(new UserEvent($user), Events::MAIL_ACCOUNT_CREATED);
    }

    public function isRegistrationOpen(): bool
    {
        return $this->registrationOpen;
    }

    private function buildUser(Registration $registration): User
    {
        $user = new User();
        $user->setEmail(strtolower((string)$registration->getEmail()));
        $user->setRoles([Roles::USER]);

        if (null !== $domain = $this->domainGuesser->guess($registration->getEmail())) {
            $user->setDomain($domain);
        }

        if (null !== $voucher = $this->manager->getRepository(Voucher::class)->findByCode($registration->getVoucher())) {
            $voucher->setRedeemedTime(new DateTime());

            $user->setInvitationVoucher($voucher);

            $this->manager->flush();

            return $user;
        }

        return $user;
    }
}
