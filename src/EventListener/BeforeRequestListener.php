<?php

namespace App\EventListener;

use App\Entity\User;
use App\Enum\Roles;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

/**
 * @author tim <tim@systemli.org>
 */
class BeforeRequestListener implements EventSubscriberInterface
{
    /**
     * @var ObjectManager
     */
    protected $manager;
    /**
     * @var Security
     */
    protected $security;

    /**
     * BeforeRequestListener constructor.
     *
     * @param ObjectManager $manager
     * @param Security      $security
     */
    public function __construct(ObjectManager $manager, Security $security)
    {
        $this->manager = $manager;
        $this->security = $security;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($user = $this->getNonAdminUser()) {
            $filter = $this->manager->getFilters()->enable('domain_filter');
            $filter->setParameter('domainId', $user->getDomain()->getId());
        }
    }

    /**
     * @return User|null
     */
    public function getNonAdminUser(): ?User
    {
        $user = $this->security->getUser();

        // Only interested in Non-Admin logged-in users
        if (null === $user || $this->security->isGranted(Roles::ADMIN)) {
            return null;
        }

        return $this->manager->getRepository(User::class)->findByEmail($user->getUsername());
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => [['onKernelRequest']]];
    }
}
