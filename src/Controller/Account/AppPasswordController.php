<?php

declare(strict_types=1);

namespace App\Controller\Account;

use App\Entity\AppPassword;
use App\Entity\User;
use App\Form\AppPasswordCreateType;
use App\Form\Model\AppPasswordCreate;
use App\Repository\AppPasswordRepository;
use App\Service\AppPasswordManager;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AppPasswordController extends AbstractController
{
    public function __construct(
        private readonly AppPasswordManager $appPasswordManager,
        private readonly AppPasswordRepository $appPasswordRepository,
    ) {
    }

    #[Route(path: '/account/app-passwords', name: 'account_app_passwords', methods: ['GET'])]
    public function show(): Response
    {
        return $this->renderPage($this->createCreateForm(new AppPasswordCreate()));
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/account/app-passwords', name: 'account_app_passwords_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = new AppPasswordCreate();
        $form = $this->createCreateForm($data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $this->appPasswordManager->create($user, $data->getName(), $data->getPassword());

            // Rendered directly so the plaintext never ends up in a URL or session
            return $this->renderPage($this->createCreateForm(new AppPasswordCreate()), $plain);
        }

        return $this->renderPage($form);
    }

    #[Route(path: '/account/app-passwords/{id}/delete', name: 'account_app_passwords_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, int $id): RedirectResponse
    {
        $appPassword = $this->appPasswordRepository->findOneBy(['id' => $id, 'user' => $this->getUser()]);
        if (!$appPassword instanceof AppPassword) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('delete_app_password_'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'flashes.app-password-deletion-failed');

            return $this->redirectToRoute('account_app_passwords');
        }

        $this->appPasswordManager->delete($appPassword);
        $this->addFlash('success', 'flashes.app-password-deletion-successful');

        return $this->redirectToRoute('account_app_passwords');
    }

    private function createCreateForm(AppPasswordCreate $data): FormInterface
    {
        return $this->createForm(AppPasswordCreateType::class, $data, [
            'action' => $this->generateUrl('account_app_passwords_create'),
            'method' => 'post',
        ]);
    }

    private function renderPage(FormInterface $form, ?string $plain = null): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('Account/app_passwords.html.twig', [
            'form' => $form,
            'app_password' => $plain,
            'app_passwords' => $this->appPasswordRepository->findByUser($user),
        ]);
    }
}
