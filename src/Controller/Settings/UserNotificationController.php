<?php

declare(strict_types=1);

namespace App\Controller\Settings;

use App\Enum\UserNotificationType;
use App\Service\UserNotificationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserNotificationController extends AbstractController
{
    public function __construct(
        private readonly UserNotificationManager $manager,
    ) {
    }

    #[Route('/settings/user-notifications/', name: 'settings_user_notification_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->getString('search', '');
        $type = $request->query->getString('type', '');

        return $this->render('Settings/UserNotification/index.html.twig', [
            'pagination' => $this->manager->findPaginated($request->query->getInt('page', 1), $search, $type),
            'search' => $search,
            'type' => $type,
            'notificationTypes' => UserNotificationType::cases(),
        ]);
    }
}
