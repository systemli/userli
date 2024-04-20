<?php

namespace App\Controller\Api;

use App\Dto\RegistrationDto;
use App\Entity\User;
use App\Handler\RegistrationHandler;
use App\Form\Model\Registration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly RegistrationHandler $registrationHandler,
        private readonly EntityManagerInterface $manager,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {
    }

    #[Route('/api/user/register', name: 'post_user_register', methods: ['POST'], stateless: true)]
    public function register(
        #[MapRequestPayload] RegistrationDto $request
    ): JsonResponse {
        if (!$this->registrationHandler->isRegistrationOpen()) {
            return $this->json([
                'status' => 'error',
                'message' => 'registration closed'
            ], 423);
        }

        $registration = new Registration();
        $registration->setVoucher($request->voucher);
        $registration->setPlainPassword($request->getNewPassword());
        $registration->setEmail($request->email);

        if (null === $user = $this->registrationHandler->handle($registration)) {
            return $this->json([
                'status' => 'error',
                'message' => 'unknown error when creating user'
            ], 500);
        }

        $recoveryToken = $user->getPlainRecoveryToken();
        $jwtToken = $this->jwtManager->create($user);

        $user->eraseCredentials();
        return $this->json([
            'message' => 'success',
            'recoveryToken' => $recoveryToken,
            'token' => $jwtToken,
        ], 200);
    }
}
