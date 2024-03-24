<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @param Request $request
     * @return RedirectResponse
     */
    #[Route(path: '/login', name: 'login_no_locale')]
    public function indexNoLocale(Request $request): RedirectResponse
    {
        $supportedLocales = (array)$this->getParameter('supported_locales');
        $preferredLanguage = $request->getPreferredLanguage($supportedLocales);
        $locale = $preferredLanguage ?: $request->getLocale();

        return $this->redirectToRoute('login', ['_locale' => $locale]);
    }

    /**
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    #[Route(path: '/{_locale<%locales%>}/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('Security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * @return void
     */
    #[Route(path: '/logout', name: 'logout', methods: ['GET'])]
    public function logout(): void
    {

    }
}
