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

/**
 * @author louis <louis@systemli.org>
 */
class RegistrationHandler
{
    const REGISTRATION_LIMIT = 9999;
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
     * @var
     */
    private $primaryDomain;

    /**
     * Constructor.
     *
     * @param ObjectManager            $manager
     * @param DomainGuesser            $domainGuesser
     * @param EventDispatcherInterface $eventDispatcher
     * @param PasswordUpdater          $passwordUpdater
     * @param MailCryptKeyHandler      $mailCryptKeyHandler
     * @param RecoveryTokenHandler     $recoveryTokenHandler
     * @param bool                     $hasSinaBox
     * @param string                   $primaryDomain
     */
    public function __construct(
        ObjectManager $manager,
        DomainGuesser $domainGuesser,
        EventDispatcherInterface $eventDispatcher,
        PasswordUpdater $passwordUpdater,
        MailCryptKeyHandler $mailCryptKeyHandler,
        RecoveryTokenHandler $recoveryTokenHandler,
        bool $hasSinaBox,
        string $primaryDomain
    ) {
        $this->manager = $manager;
        $this->domainGuesser = $domainGuesser;
        $this->eventDispatcher = $eventDispatcher;
        $this->passwordUpdater = $passwordUpdater;
        $this->mailCryptKeyHandler = $mailCryptKeyHandler;
        $this->recoveryTokenHandler = $recoveryTokenHandler;
        $this->hasSinaBox = $hasSinaBox;
        $this->primaryDomain = $primaryDomain;
    }

    /**
     * @param Registration $registration
     *
     * @throws \Exception
     */
    public function handle(Registration $registration)
    {
        if (!$this->canRegister()) {
            throw new \Exception('The Registration Limit reached!');
        }

        $user = $this->buildUser($registration);

        $this->passwordUpdater->updatePassword($user);
        $this->mailCryptKeyHandler->create($user);
        $this->recoveryTokenHandler->create($user);
        $user->eraseCredentials();
        $user->erasePlainMailCryptPrivateKey();
        $this->manager->persist($user);
        $this->manager->flush();

        $this->eventDispatcher->dispatch(Events::MAIL_ACCOUNT_CREATED, new UserEvent($user));
    }

    /**
     * @return bool
     */
    public function canRegister()
    {
        $count = $this->manager->getRepository('App:User')->count([]);

        if (!$this->hasSinaBox && $count > self::REGISTRATION_LIMIT) {
            return false;
        }

        return true;
    }

    /**
     * @param Registration $registration
     *
     * @return User
     * @throws \Exception
     */
    private function buildUser(Registration $registration)
    {
        $user = new User();
        $user->setEmail(strtolower($registration->getEmail()));
        $user->setPlainPassword($registration->getPlainPassword());
        $user->setRoles(array(Roles::USER));
        $user->setCreationTime(new \DateTime());
        $user->setUpdatedTime(new \DateTime());

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
