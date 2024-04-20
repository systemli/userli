<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;

class LoginController extends AbstractController
{
    #[Route('/api/user/login', name: 'api_login', methods: ['POST'])]
    public function apilogin() {}

    #[Route('/api/user/login/2fa', name: 'api_login_2fa', methods: ['POST'])]
    public function apilogin2fa(TokenInterface $token): Response
    {
        // TODO: should be handled by firewall?
        if (!$token instanceof TwoFactorTokenInterface) {
            $error = $this->createAccessDeniedException("User not in 2fa process");

            $jsonResponse = new Response(json_encode($error), Response::HTTP_BAD_REQUEST);
            $jsonResponse->headers->set('Content-Type', 'application/json');
            return $jsonResponse;
        }
    }
}
