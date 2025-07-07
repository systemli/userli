<?php

namespace App\Controller;

use App\Entity\OpenPgpKey;
use App\Entity\User;
use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgDataException;
use App\Exception\NoGpgKeyForUserException;
use App\Form\Model\OpenPgpKey as OpenPgpKeyModel;
use App\Form\OpenPgpKeyType;
use App\Handler\WkdHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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

        $openPgp = new OpenPgpKeyModel();
        $openPgpKeyForm = $this->createForm(OpenPgpKeyType::class, $openPgp);

        $openPgpKey = $this->wkdHandler->getKey($user);

        return $this->render(
            'Start/openpgp.html.twig',
            [
                'user' => $user,
                'user_domain' => $user->getDomain(),
                'openpgp_form' => $openPgpKeyForm->createView(),
                'openpgp_id' => $openPgpKey->getKeyId(),
                'openpgp_fingerprint' => $openPgpKey->getKeyFingerprint(),
                'openpgp_expiretime' => $openPgpKey->getKeyExpireTime(),
            ]
        );
    }

    #[Route(path: '/openpgp', name: 'openpgp_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $openPgp = new OpenPgpKeyModel();
        $openPgpKeyForm = $this->createForm(OpenPgpKeyType::class, $openPgp);
        $openPgpKeyForm->handleRequest($request);

        if ($openPgpKeyForm->isSubmitted() && $openPgpKeyForm->isValid()) {
            /** @var UploadedFile $keyFile */
            $keyFile = $openPgpKeyForm->get('keyFile')->getData();
            /** @var string $keyText */
            $keyText = $openPgpKeyForm->get('keyText')->getData();

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
                'user_domain' => $user->getDomain(),
                'openpgp_form' => $openPgpKeyForm->createView(),
                'openpgp_id' => $openPgpKey->getKeyId(),
                'openpgp_fingerprint' => $openPgpKey->getKeyFingerprint(),
                'openpgp_expiretime' => $openPgpKey->getKeyExpireTime(),
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
}
