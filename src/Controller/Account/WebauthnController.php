<?php

declare(strict_types=1);

namespace App\Controller\Account;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use App\Exception\WebauthnException;
use App\Form\Model\PasswordConfirmation;
use App\Form\Model\WebauthnCredentialCreate;
use App\Form\PasswordConfirmationType;
use App\Form\WebauthnCredentialCreateType;
use App\Helper\TotpBackupCodeGenerator;
use App\Repository\WebauthnCredentialRepository;
use App\Service\WebauthnService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WebauthnController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly WebauthnService $webauthnService,
        private readonly WebauthnCredentialRepository $repository,
        private readonly TotpBackupCodeGenerator $totpBackupCodeGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/account/webauthn', name: 'account_webauthn', methods: ['GET'])]
    public function show(): Response
    {
        return $this->renderPage($this->createCreateForm(new WebauthnCredentialCreate()));
    }

    #[Route(path: '/account/webauthn', name: 'account_webauthn_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = new WebauthnCredentialCreate();
        $form = $this->createCreateForm($data);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->renderPage($form);
        }

        if (null !== $this->repository->findOneBy(['user' => $user, 'name' => $data->getName()])) {
            $form->get('name')->addError(new FormError($this->translator->trans('form.unique-field', [], 'validators')));

            return $this->renderPage($form);
        }

        // Backup codes are shared by all second factors, so they are only created for the first one
        $firstFactor = !$user->isTwofactorEnabled();

        try {
            $this->webauthnService->verifyRegistration($user, $data->getResponse(), $data->getName());
        } catch (WebauthnException) {
            $form->addError(new FormError($this->translator->trans('form.webauthn-failed', [], 'validators')));

            return $this->renderPage($form);
        }

        if ($firstFactor) {
            $user->setTotpBackupCodes($this->totpBackupCodeGenerator->generate());
            $this->manager->flush();

            return $this->redirectToRoute('account_twofactor_backup_codes');
        }

        $this->addFlash('success', 'flashes.webauthn-creation-successful');

        return $this->redirectToRoute('account_webauthn');
    }

    #[Route(path: '/account/webauthn/{id}/delete', name: 'account_webauthn_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $credential = $this->repository->findOneBy(['id' => $id, 'user' => $user]);
        if (!$credential instanceof WebauthnCredential) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(PasswordConfirmationType::class, new PasswordConfirmation());
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'flashes.password-confirmation-failed');

            return $this->redirectToRoute('account_webauthn');
        }

        $user->getWebauthnCredentials()->removeElement($credential);
        if (!$user->isTwofactorEnabled()) {
            $user->setTotpBackupCodes([]);
        }

        $this->manager->flush();
        $this->addFlash('success', 'flashes.webauthn-deletion-successful');

        return $this->redirectToRoute('account_webauthn');
    }

    private function createCreateForm(WebauthnCredentialCreate $data): FormInterface
    {
        return $this->createForm(WebauthnCredentialCreateType::class, $data, [
            'action' => $this->generateUrl('account_webauthn_create'),
            'method' => 'post',
        ]);
    }

    private function renderPage(FormInterface $form): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $credentials = $this->repository->findByUser($user);

        return $this->render('Account/webauthn.html.twig', [
            'form' => $form,
            'options' => $this->webauthnService->createRegistrationOptions($user),
            'credentials' => $credentials,
            'user' => $user,
        ]);
    }
}
