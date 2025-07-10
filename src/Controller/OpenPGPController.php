<?php

namespace App\Controller;

use App\Entity\User;
use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgDataException;
use App\Exception\NoGpgKeyForUserException;
use App\Form\Model\Delete;
use App\Form\Model\OpenPgpKey as OpenPgpKeyModel;
use App\Form\OpenPgpDeleteType;
use App\Form\OpenPgpKeyType;
use App\Handler\WkdHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OpenPGPController extends AbstractController
{
    public function __construct(private readonly WkdHandler $wkdHandler)
    {
    }

    #[Route(path: '/openpgp', name: 'openpgp', methods: ['GET'])]
    public function show(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(OpenPgpKeyType::class, new OpenPgpKeyModel());
        $openPgpKey = $this->wkdHandler->getKey($user);

        return $this->render(
            'Start/openpgp.html.twig',
            [
                'user' => $user,
                'form' => $form,
                'openpgp_key' => $openPgpKey,
            ]
        );
    }

    #[Route(path: '/openpgp', name: 'openpgp_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(OpenPgpKeyType::class, new OpenPgpKeyModel());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $keyFile */
            $keyFile = $form->get('keyFile')->getData();
            /** @var string $keyText */
            $keyText = $form->get('keyText')->getData();

            if ($keyFile) {
                $content = file_get_contents($keyFile->getPathname());
                $this->importOpenPgpKey($user, $content);
            } elseif ($keyText) {
                $this->importOpenPgpKey($user, $keyText);
            }

            return $this->redirectToRoute('openpgp');
        }

        $openPgpKey = $this->wkdHandler->getKey($user);

        return $this->render(
            'Start/openpgp.html.twig',
            [
                'user' => $user,
                'form' => $form,
                'openpgp_key' => $openPgpKey,
            ]
        );
    }

    private function importOpenPgpKey(User $user, string $key): void
    {
        try {
            $this->wkdHandler->importKey($key, $user);
            $this->addFlash('success', 'flashes.openpgp-key-upload-successful');
        } catch (NoGpgDataException) {
            $this->addFlash('error', 'flashes.openpgp-key-upload-error-no-openpgp');
        } catch (NoGpgKeyForUserException) {
            $this->addFlash('error', 'flashes.openpgp-key-upload-error-no-keys');
        } catch (MultipleGpgKeysForUserException) {
            $this->addFlash('error', 'flashes.openpgp-key-upload-error-multiple-keys');
        }
    }

    #[Route(path: '/openpgp/delete', name: 'openpgp_delete', methods: ['GET'])]
    public function delete(): RedirectResponse|Response
    {
        $form = $this->createForm(OpenPgpDeleteType::class, new Delete());

        return $this->render(
            'OpenPgp/delete.html.twig',
            [
                'form' => $form,
                'user' => $this->getUser(),
            ]
        );
    }

    #[Route(path: '/openpgp/delete', name: 'openpgp_delete_submit', methods: ['POST'])]
    public function deleteSubmit(Request $request): RedirectResponse
    {
        $form = $this->createForm(OpenPgpDeleteType::class, new Delete());
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->wkdHandler->deleteKey($this->getUser()->getEmail());

            $request->getSession()->getFlashBag()->add('success', 'flashes.openpgp-deletion-successful');
        }

        return $this->redirectToRoute('openpgp');
    }
}
