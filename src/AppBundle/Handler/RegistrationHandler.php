<?php

namespace AppBundle\Handler;

use AppBundle\Counter\UserCounter;
use AppBundle\Entity\User;
use AppBundle\Enum\Roles;
use AppBundle\Event\Events;
use AppBundle\Event\UserEvent;
use AppBundle\Form\Model\Registration;
use AppBundle\Guesser\DomainGuesser;
use AppBundle\Helper\PasswordUpdater;
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
     * @var UserCounter
     */
    private $userCounter;
    /**
     * @var bool
     */
    private $hasSinaBox;

    /**
     * Constructor.
     *
     * @param ObjectManager            $manager
     * @param DomainGuesser            $domainGuesser
     * @param EventDispatcherInterface $eventDispatcher
     * @param PasswordUpdater          $passwordUpdater
     * @param UserCounter              $userCounter
     * @param bool                     $hasSinaBox
     */
    public function __construct(
        ObjectManager $manager,
        DomainGuesser $domainGuesser,
        EventDispatcherInterface $eventDispatcher,
        PasswordUpdater $passwordUpdater,
        UserCounter $userCounter,
        $hasSinaBox
    ) {
        $this->manager = $manager;
        $this->domainGuesser = $domainGuesser;
        $this->eventDispatcher = $eventDispatcher;
        $this->passwordUpdater = $passwordUpdater;
        $this->userCounter = $userCounter;
        $this->hasSinaBox = $hasSinaBox;
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
        $this->manager->persist($user);
        $this->manager->flush();

        $this->eventDispatcher->dispatch(Events::MAIL_ACCOUNT_CREATED, new UserEvent($user));
    }

    /**
     * @return bool
     */
    public function canRegister()
    {
        $count = $this->userCounter->getCount();

        if (!$this->hasSinaBox && $count > self::REGISTRATION_LIMIT) {
            return false;
        }

        return true;
    }

    /**
     * @param Registration $registration
     *
     * @return User
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

        if (null !== $voucher = $this->manager->getRepository('AppBundle:Voucher')->findByCode($registration->getVoucher())) {
            $voucher->setRedeemedTime(new \DateTime());

            $user->setInvitationVoucher($voucher);

            $this->manager->flush();

            return $user;
        }

        return $user;
    }
}
