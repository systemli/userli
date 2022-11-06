<?php

namespace App\EventListener;

use App\Entity\User;
use App\Enum\Roles;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

class BeforeRequestListener implements EventSubscriberInterface
{
    protected EntityManagerInterface $entityManager;
    protected Security $security;

    /**
     * BeforeRequestListener constructor.
     */
    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if ($user = $this->getNonAdminUser()) {
            $filter = $this->entityManager->getFilters()->enable('domain_filter');
            $filter->setParameter('domainId', $user->getDomain()->getId());
        }
    }

    public function getNonAdminUser(): ?User
    {
        $user = $this->security->getUser();

        // Only interested in Non-Admin logged-in users
        if (null === $user || $this->security->isGranted(Roles::ADMIN)) {
            return null;
        }

        return $this->entityManager->getRepository(User::class)->findByEmail($user->getUserIdentifier());
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => [['onKernelRequest']]];
    }
}
