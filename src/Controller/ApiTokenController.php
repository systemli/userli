<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ApiToken;
use App\Form\ApiTokenType;
use App\Service\ApiTokenManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;

final class ApiTokenController extends AbstractController
{
    public function __construct(private readonly ApiTokenManager $apiTokenManager)
    {
    }

    #[Route('/settings/api/', name: 'settings_api_show', methods: ['GET'])]
    public function show(Request $request): Response
    {
        $newToken = null;
        $session = $request->getSession();
        assert($session instanceof Session);
        if ($session->getFlashBag()->has('newToken')) {
            $newToken = $session->getFlashBag()->get('newToken')[0];
        }

        $tokens = $this->apiTokenManager->findAll();

        return $this->render('Settings/Api/show.html.twig', [
            'tokens' => $tokens,
            'newToken' => $newToken,
        ]);
    }

    #[Route('/settings/api/create', name: 'settings_api_create', methods: ['GET'])]
    public function create(): Response
    {
        $form = $this->createForm(ApiTokenType::class, null, [
            'action' => $this->generateUrl('settings_api_create_post'),
            'method' => 'POST',
        ]);

        return $this->render('Settings/Api/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/settings/api/create', name: 'settings_api_create_post', methods: ['POST'])]
    public function createSubmit(Request $request): Response
    {
        $form = $this->createForm(ApiTokenType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $plainToken = $this->apiTokenManager->generateToken();
            $this->apiTokenManager->create($plainToken, $data->getName(), $data->getScopes());

            $this->addFlash('newToken', $plainToken);

            return $this->redirectToRoute('settings_api_show');
        }

        return $this->render('Settings/Api/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/settings/api/delete/{id}', name: 'settings_api_delete', methods: ['POST'])]
    public function delete(
        #[MapEntity(class: ApiToken::class, mapping: ['id' => 'id'])] ?ApiToken $apiToken,
        Request $request,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('delete_api_token_'.$apiToken->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('settings_api_show');
        }

        $this->apiTokenManager->delete($apiToken);

        $this->addFlash('success', 'settings.api.delete.success');

        return $this->redirectToRoute('settings_api_show');
    }
}
