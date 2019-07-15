<?php

namespace App\EventListener;

use App\Entity\User;
use App\Enum\Roles;
use Doctrine\ORM\EntityManagerInterface;
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
     * @var EntityManagerInterface
     */
    protected $entityManager;
    /**
     * @var Security
     */
    protected $security;

    /**
     * BeforeRequestListener constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param Security               $security
     */
    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($user = $this->getNonAdminUser()) {
            $filter = $this->entityManager->getFilters()->enable('domain_filter');
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

        return $this->entityManager->getRepository(User::class)->findByEmail($user->getUsername());
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => [['onKernelRequest']]];
    }
}
