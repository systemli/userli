<?php

namespace App\Twig;

use App\Entity\UserNotification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserNotificationExtension extends AbstractExtension
{
    public function __construct(
        readonly private Security               $security,
        readonly private EntityManagerInterface $entityManager,
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('hasNotifications', $this->hasNotifications(...)),
        ];
    }

    public function hasNotifications(string $type = null): bool
    {
        $user = $this->security->getUser();
        if ($user === null) {
            return false;
        }

        if ($type !== null) {
            $notifications = $this->entityManager->getRepository(UserNotification::class)
                ->findBy(['user' => $user, 'type' => $type]);

            return count($notifications) > 0;
        }

        $notifications = $this->entityManager->getRepository(UserNotification::class)
            ->findBy(['user' => $user]);

        return count($notifications) > 0;
    }
}
