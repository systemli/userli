<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Enum\Roles;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class BeforeRequestListener implements EventSubscriberInterface
{
    /**
     * BeforeRequestListener constructor.
     */
    public function __construct(protected EntityManagerInterface $entityManager, protected Security $security)
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (($user = $this->getNonAdminUser()) !== null) {
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

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => [['onKernelRequest']]];
    }
}
