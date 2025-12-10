<?php

declare(strict_types=1);

namespace App\Handler;

use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Event\UserEvent;
use App\Form\Model\Registration;
use App\Guesser\DomainGuesser;
use App\Helper\PasswordUpdater;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class RegistrationHandler
{
    public function __construct(
        private EntityManagerInterface $manager,
        private DomainGuesser $domainGuesser,
        private EventDispatcherInterface $eventDispatcher,
        private PasswordUpdater $passwordUpdater,
        private MailCryptKeyHandler $mailCryptKeyHandler,
        private RecoveryTokenHandler $recoveryTokenHandler,
        #[Autowire(env: 'REGISTRATION_OPEN')]
        private bool $registrationOpen,
        #[Autowire(env: 'MAIL_CRYPT')]
        private bool $mailCrypt,
    ) {
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

        // Update password, generate MailCrypt keys, generate recovery token
        // key material for mailCrypt is always generated, but only enabled if MAIL_CRYPT >= 2
        $mailCryptEnable = $this->mailCrypt >= 2;
        $this->passwordUpdater->updatePassword($user, $registration->getPlainPassword());
        $this->mailCryptKeyHandler->create($user, $registration->getPlainPassword(), $mailCryptEnable);
        $this->recoveryTokenHandler->create($user);

        // We used to erase sensitive data here, but it's now done in RegistrationController
        // as we need to print the plainRecoveryToken beforehand

        $this->manager->persist($user);
        $this->manager->flush();

        $this->eventDispatcher->dispatch(new UserEvent($user), UserEvent::USER_CREATED);
    }

    public function isRegistrationOpen(): bool
    {
        return $this->registrationOpen;
    }

    private function buildUser(Registration $registration): User
    {
        $user = new User();
        $user->setEmail(strtolower((string) $registration->getEmail()));
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
