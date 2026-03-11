<?php

declare(strict_types=1);

namespace App\Controller\Settings;

use App\Entity\OpenPgpKey;
use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgDataException;
use App\Exception\NoGpgKeyForUserException;
use App\Form\Model\OpenPgpKey as OpenPgpKeyModel;
use App\Form\OpenPgpKeyType;
use App\Service\OpenPgpKeyManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OpenPgpKeyController extends AbstractController
{
    public function __construct(
        private readonly OpenPgpKeyManager $manager,
    ) {
    }

    #[Route('/settings/openpgp-keys/', name: 'settings_openpgp_key_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->getString('search', '');

        return $this->render('Settings/OpenPgpKey/index.html.twig', [
            'pagination' => $this->manager->findPaginated($request->query->getInt('page', 1), $search),
            'search' => $search,
        ]);
    }

    #[Route('/settings/openpgp-keys/import', name: 'settings_openpgp_key_import', methods: ['GET'])]
    public function import(): Response
    {
        $form = $this->createForm(OpenPgpKeyType::class, new OpenPgpKeyModel(), [
            'email_visible' => true,
            'action' => $this->generateUrl('settings_openpgp_key_import_post'),
            'method' => 'POST',
        ]);

        return $this->render('Settings/OpenPgpKey/import.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/settings/openpgp-keys/import', name: 'settings_openpgp_key_import_post', methods: ['POST'])]
    public function importSubmit(Request $request): Response
    {
        $model = new OpenPgpKeyModel();
        $form = $this->createForm(OpenPgpKeyType::class, $model, [
            'email_visible' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $email */
            $email = $model->getEmail();

            /** @var UploadedFile|null $keyFile */
            $keyFile = $form->get('keyFile')->getData();
            /** @var string|null $keyText */
            $keyText = $model->getKeyText();

            $keyContent = null;
            if (null !== $keyFile) {
                $keyContent = file_get_contents($keyFile->getPathname());
            } elseif (null !== $keyText) {
                $keyContent = $keyText;
            }

            if (null !== $keyContent && false !== $keyContent) {
                try {
                    $this->manager->importKey($keyContent, $email);
                    $this->addFlash('success', 'settings.openpgp-key.import.success');

                    return $this->redirectToRoute('settings_openpgp_key_index');
                } catch (NoGpgDataException) {
                    $this->addFlash('error', 'settings.openpgp-key.import.error.no-openpgp');
                } catch (NoGpgKeyForUserException) {
                    $this->addFlash('error', 'settings.openpgp-key.import.error.no-keys');
                } catch (MultipleGpgKeysForUserException) {
                    $this->addFlash('error', 'settings.openpgp-key.import.error.multiple-keys');
                }
            }
        }

        return $this->render('Settings/OpenPgpKey/import.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/settings/openpgp-keys/delete/{id}', name: 'settings_openpgp_key_delete', methods: ['POST'])]
    public function delete(#[MapEntity] OpenPgpKey $openPgpKey, Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_openpgp_key_'.$openPgpKey->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('settings_openpgp_key_index');
        }

        $this->manager->deleteKey((string) $openPgpKey->getEmail());
        $this->addFlash('success', 'settings.openpgp-key.delete.success');

        return $this->redirectToRoute('settings_openpgp_key_index');
    }
}
