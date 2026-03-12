<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Enum\Roles;
use App\Enum\UserNotificationType;
use App\Service\UserNotificationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(Roles::ADMIN)]
final class UserNotificationController extends AbstractController
{
    public function __construct(
        private readonly UserNotificationManager $manager,
    ) {
    }

    #[Route('/admin/user-notifications/', name: 'admin_user_notification_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->getString('search', '');
        $type = $request->query->getString('type', '');

        return $this->render('Admin/UserNotification/index.html.twig', [
            'pagination' => $this->manager->findPaginated($request->query->getInt('page', 1), $search, $type),
            'search' => $search,
            'type' => $type,
            'notificationTypes' => UserNotificationType::cases(),
        ]);
    }
}
