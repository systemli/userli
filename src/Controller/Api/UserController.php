<?php

namespace App\Controller\Api;

use App\Dto\PasswordDto;
use App\Dto\PasswordChangeDto;
use App\Entity\User;
use App\Handler\MailCryptKeyHandler;
use App\Handler\DeleteHandler;
use App\Helper\PasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class UserController extends AbstractController
{
    public function __construct(
        private readonly DeleteHandler $deleteHandler,
        private readonly PasswordUpdater $passwordUpdater,
        private readonly MailCryptKeyHandler $mailCryptKeyHandler,
        private readonly EntityManagerInterface $manager,
        private readonly Security $security,
    ) {
    }

    #[Route('/api/user', name: 'get_user', methods: ['GET'], stateless: true)]
    public function getSelf(
        #[CurrentUser] User $user
    ): JsonResponse {
        return $this->json([
            'status' => 'success',
            'username' => $user->getEmail(),
        ], 200);
    }

    #[Route('/api/user', name: 'patch_user', methods: ['PATCH'], stateless: true)]
    public function patchSelf(
        #[MapRequestPayload] PasswordChangeDto $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $user->setPlainPassword($request->getNewPassword());
        $this->passwordUpdater->updatePassword($user);

        // TODO/DISCUSS: move to event listener
        // Reencrypt the MailCrypt key with new password
        if ($user->hasMailCryptSecretBox()) {
            $this->mailCryptKeyHandler->update($user, $request->getPassword());
        }
        $this->manager->flush();

        $user->eraseCredentials();
        return $this->json(['status' => 'success'], 200);
    }

    /**
     * TODO: invalidate JWT Token?
     * Delegates password validation to Password
     */
    #[Route('/api/user', name: 'delete_user', methods: ['DELETE'], stateless: true)]
    public function deleteSelf(
        #[MapRequestPayload] PasswordDto $request,
        #[CurrentUser] User $user
    ): JsonResponse {
        $this->deleteHandler->deleteUser($user);

        return $this->json(['status' => 'success'], 200);
    }
}
