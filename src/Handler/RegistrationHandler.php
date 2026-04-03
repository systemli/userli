<?php

declare(strict_types=1);

namespace App\Handler;

use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\MailCrypt;
use App\Enum\Roles;
use App\Event\UserEvent;
use App\Form\Model\Registration;
use App\Helper\PasswordUpdater;
use App\Service\DomainGuesser;
use App\Service\SettingsService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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
        private SettingsService $settingsService,
    ) {
    }

    /**
     * @throws Exception
     */
    public function handle(Registration $registration): void
    {
        $user = null;
        $this->manager->wrapInTransaction(function () use ($registration, &$user): void {
            // Create user
            $user = $this->buildUser($registration);

            // Update password, generate MailCrypt keys, generate recovery token
            // key material for mailCrypt is always generated, but only enabled if MAIL_CRYPT >= 2
            $mailCrypt = MailCrypt::from($this->settingsService->get('mail_crypt'));
            $mailCryptEnable = $mailCrypt->isAtLeast(MailCrypt::ENABLED_ENFORCE_NEW_USERS);
            $this->passwordUpdater->updatePassword($user, $registration->getPassword());
            $this->mailCryptKeyHandler->create($user, $registration->getPassword(), $mailCryptEnable);
            $this->recoveryTokenHandler->create($user);

            // We used to erase sensitive data here, but it's now done in RegistrationController
            // as we need to print the plainRecoveryToken beforehand

            $this->manager->persist($user);
        });

        $this->eventDispatcher->dispatch(new UserEvent($user), UserEvent::USER_CREATED);
    }

    private function buildUser(Registration $registration): User
    {
        $user = new User(strtolower((string) $registration->getEmail()));
        $user->setRoles([Roles::USER]);

        if (null !== $domain = $this->domainGuesser->guess($registration->getEmail())) {
            $user->setDomain($domain);
        }

        if (null !== $voucher = $this->manager->getRepository(Voucher::class)->findByCode($registration->getVoucher())) {
            $voucher->setRedeemedTime(new DateTimeImmutable());

            $user->setInvitationVoucher($voucher);

            return $user;
        }

        return $user;
    }
}
