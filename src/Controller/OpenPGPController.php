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
use Symfony\Component\Routing\Annotation\Route;

class OpenPGPController extends AbstractController
{
    public function __construct(private readonly WkdHandler $wkdHandler)
    {
    }

    #[Route(path: '/openpgp', name: 'openpgp')]
    public function openPgp(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $openPgp = new OpenPgpKeyModel();
        $openPgpKeyForm = $this->createForm(
            OpenPgpKeyType::class,
            $openPgp,
            [
                'action' => $this->generateUrl('openpgp'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $openPgpKeyForm->handleRequest($request);

            if ($openPgpKeyForm->isSubmitted() && $openPgpKeyForm->isValid()) {
                /** @var UploadedFile $keyFile */
                $keyFile = $openPgpKeyForm->get('keyFile')->getData();
                /** @var string $keyText */
                $keyText = $openPgpKeyForm->get('keyText')->getData();

                if ($keyFile) {
                    $content = file_get_contents($keyFile->getPathname());
                    $openPgpKey = $this->importOpenPgpKey($request, $user, $content);
                } elseif ($keyText) {
                    $openPgpKey = $this->importOpenPgpKey($request, $user, $keyText);
                }
            }
        }

        if (!isset($openPgpKey) || null === $openPgpKey->getKeyId()) {
            $openPgpKey = $this->wkdHandler->getKey($user);
        }

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

    private function importOpenPgpKey(Request $request, User $user, string $key): OpenPgpKey
    {
        $openPgpKey = new OpenPgpKey();
        try {
            $openPgpKey = $this->wkdHandler->importKey($key, $user);
            $request->getSession()->getFlashBag()->add('success', 'flashes.openpgp-key-upload-successful');
        } catch (NoGpgDataException) {
            $request->getSession()->getFlashBag()->add('error', 'flashes.openpgp-key-upload-error-no-openpgp');
        } catch (NoGpgKeyForUserException) {
            $request->getSession()->getFlashBag()->add('error', 'flashes.openpgp-key-upload-error-no-keys');
        } catch (MultipleGpgKeysForUserException) {
            $request->getSession()->getFlashBag()->add('error', 'flashes.openpgp-key-upload-error-multiple-keys');
        }

        return $openPgpKey;
    }
}
