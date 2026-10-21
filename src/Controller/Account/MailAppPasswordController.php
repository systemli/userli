<?php

declare(strict_types=1);

namespace App\Controller\Account;

use App\Entity\MailAppPassword;
use App\Entity\User;
use App\Form\MailAppPasswordCreateType;
use App\Form\Model\MailAppPasswordCreate;
use App\Repository\MailAppPasswordRepository;
use App\Service\MailAppPasswordManager;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MailAppPasswordController extends AbstractController
{
    public function __construct(
        private readonly MailAppPasswordManager $mailAppPasswordManager,
        private readonly MailAppPasswordRepository $mailAppPasswordRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/account/mail-app-passwords', name: 'account_mail_app_passwords', methods: ['GET'])]
    public function show(): Response
    {
        return $this->renderPage($this->createCreateForm(new MailAppPasswordCreate()));
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/account/mail-app-passwords', name: 'account_mail_app_passwords_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = new MailAppPasswordCreate();
        $form = $this->createCreateForm($data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null !== $this->mailAppPasswordRepository->findOneBy(['user' => $user, 'name' => $data->getName()])) {
                $form->get('name')->addError(new FormError($this->translator->trans('form.unique-field', [], 'validators')));

                return $this->renderPage($form);
            }

            $plain = $this->mailAppPasswordManager->create($user, $data->getName(), $data->getPassword());

            // Rendered directly so the plaintext never ends up in a URL or session
            return $this->renderPage($this->createCreateForm(new MailAppPasswordCreate()), $plain);
        }

        return $this->renderPage($form);
    }

    #[Route(path: '/account/mail-app-passwords/{id}/delete', name: 'account_mail_app_passwords_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, int $id): RedirectResponse
    {
        $mailAppPassword = $this->mailAppPasswordRepository->findOneBy(['id' => $id, 'user' => $this->getUser()]);
        if (!$mailAppPassword instanceof MailAppPassword) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('delete_mail_app_password_'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'flashes.mail-app-password-deletion-failed');

            return $this->redirectToRoute('account_mail_app_passwords');
        }

        $this->mailAppPasswordManager->delete($mailAppPassword);
        $this->addFlash('success', 'flashes.mail-app-password-deletion-successful');

        return $this->redirectToRoute('account_mail_app_passwords');
    }

    private function createCreateForm(MailAppPasswordCreate $data): FormInterface
    {
        return $this->createForm(MailAppPasswordCreateType::class, $data, [
            'action' => $this->generateUrl('account_mail_app_passwords_create'),
            'method' => 'post',
        ]);
    }

    private function renderPage(FormInterface $form, ?string $plain = null): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('Account/mail_app_passwords.html.twig', [
            'form' => $form,
            'mail_app_password' => $plain,
            'mail_app_passwords' => $this->mailAppPasswordRepository->findByUser($user),
        ]);
    }
}
