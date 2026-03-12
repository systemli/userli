<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ApiToken;
use App\Enum\Roles;
use App\Form\ApiTokenType;
use App\Service\ApiTokenManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(Roles::ADMIN)]
final class ApiTokenController extends AbstractController
{
    public function __construct(private readonly ApiTokenManager $apiTokenManager)
    {
    }

    #[Route('/admin/api/', name: 'admin_api_show', methods: ['GET'])]
    public function show(Request $request): Response
    {
        $newToken = null;
        $session = $request->getSession();
        assert($session instanceof Session);
        if ($session->getFlashBag()->has('newToken')) {
            $newToken = $session->getFlashBag()->get('newToken')[0];
        }

        $tokens = $this->apiTokenManager->findAll();

        return $this->render('Admin/Api/show.html.twig', [
            'tokens' => $tokens,
            'newToken' => $newToken,
        ]);
    }

    #[Route('/admin/api/create', name: 'admin_api_create', methods: ['GET'])]
    public function create(): Response
    {
        $form = $this->createForm(ApiTokenType::class, null, [
            'action' => $this->generateUrl('admin_api_create_post'),
            'method' => 'POST',
        ]);

        return $this->render('Admin/Api/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/api/create', name: 'admin_api_create_post', methods: ['POST'])]
    public function createSubmit(Request $request): Response
    {
        $form = $this->createForm(ApiTokenType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $plainToken = $this->apiTokenManager->generateToken();
            $this->apiTokenManager->create($plainToken, $data->getName(), $data->getScopes());

            $this->addFlash('newToken', $plainToken);

            return $this->redirectToRoute('admin_api_show');
        }

        return $this->render('Admin/Api/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/api/delete/{id}', name: 'admin_api_delete', methods: ['POST'])]
    public function delete(
        #[MapEntity] ApiToken $apiToken,
        Request $request,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('delete_api_token_'.$apiToken->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_api_show');
        }

        $this->apiTokenManager->delete($apiToken);

        $this->addFlash('success', 'admin.api.delete.success');

        return $this->redirectToRoute('admin_api_show');
    }
}
