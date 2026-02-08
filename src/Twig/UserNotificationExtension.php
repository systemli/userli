<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\UserNotification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Attribute\AsTwigFunction;

final readonly class UserNotificationExtension
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[AsTwigFunction(name: 'hasNotifications')]
    public function hasNotifications(?string $type = null): bool
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return false;
        }

        if ($type !== null) {
            $notifications = $this->entityManager->getRepository(UserNotification::class)
                ->findBy(['user' => $user, 'type' => $type]);

            return $notifications !== [];
        }

        $notifications = $this->entityManager->getRepository(UserNotification::class)
            ->findBy(['user' => $user]);

        return $notifications !== [];
    }
}
