<?php

declare(strict_types=1);

namespace App\Controller\Account;

use App\Entity\User;
use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgDataException;
use App\Exception\NoGpgKeyForUserException;
use App\Form\Model\OpenPgpKey as OpenPgpKeyModel;
use App\Form\Model\PasswordConfirmation;
use App\Form\OpenPgpKeyType;
use App\Form\PasswordConfirmationType;
use App\Handler\WkdHandler;
use App\Repository\AliasRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OpenPGPController extends AbstractController
{
    public function __construct(
        private readonly WkdHandler $wkdHandler,
        private readonly AliasRepository $aliasRepository,
    ) {
    }

    #[Route(path: '/account/openpgp', name: 'openpgp', methods: ['GET'])]
    public function show(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(OpenPgpKeyType::class, new OpenPgpKeyModel());
        $identities = $this->buildIdentities($user);

        return $this->render('Account/openpgp.html.twig', [
            'user' => $user,
            'form' => $form,
            'identities' => $identities,
        ]);
    }

    #[Route(path: '/account/openpgp', name: 'openpgp_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(OpenPgpKeyType::class, new OpenPgpKeyModel());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string|null $email */
            $email = $form->get('email')->getData();

            if (null === $email || !$this->canManageIdentity($user, $email)) {
                $this->addFlash('error', 'flashes.openpgp-key-unauthorized');

                return $this->redirectToRoute('openpgp');
            }

            /** @var UploadedFile $keyFile */
            $keyFile = $form->get('keyFile')->getData();
            /** @var string $keyText */
            $keyText = $form->get('keyText')->getData();

            if ($keyFile) {
                $content = file_get_contents($keyFile->getPathname());
                $this->importOpenPgpKey($user, $content, $email);
            } elseif ($keyText) {
                $this->importOpenPgpKey($user, $keyText, $email);
            }

            return $this->redirectToRoute('openpgp');
        }

        $identities = $this->buildIdentities($user);

        return $this->render('Account/openpgp.html.twig', [
            'user' => $user,
            'form' => $form,
            'identities' => $identities,
        ]);
    }

    private function importOpenPgpKey(User $user, string $key, string $email): void
    {
        try {
            $this->wkdHandler->importKey($key, $email, $user);
            $this->addFlash('success', 'flashes.openpgp-key-upload-successful');
        } catch (NoGpgDataException) {
            $this->addFlash('error', 'flashes.openpgp-key-upload-error-no-openpgp');
        } catch (NoGpgKeyForUserException) {
            $this->addFlash('error', 'flashes.openpgp-key-upload-error-no-keys');
        } catch (MultipleGpgKeysForUserException) {
            $this->addFlash('error', 'flashes.openpgp-key-upload-error-multiple-keys');
        }
    }

    #[Route(
        path: '/account/openpgp/delete/{email}',
        name: 'openpgp_delete_submit',
        methods: ['POST'],
        requirements: ['email' => '[^/]+'],
    )]
    public function deleteSubmit(Request $request, string $email): RedirectResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->canManageIdentity($user, $email)) {
            $this->addFlash('error', 'flashes.openpgp-key-unauthorized');

            return $this->redirectToRoute('openpgp');
        }

        $form = $this->createForm(PasswordConfirmationType::class, new PasswordConfirmation(), [
            'submit_label' => 'openpgp-delete',
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->wkdHandler->deleteKey($email);

            $this->addFlash('success', 'flashes.openpgp-deletion-successful');
        } else {
            $this->addFlash('error', 'flashes.password-confirmation-failed');
        }

        return $this->redirectToRoute('openpgp');
    }

    /**
     * Builds the list of identities for which the user can manage OpenPGP keys.
     *
     * @return array<array{email: string, type: string, key: \App\Entity\OpenPgpKey|null}>
     */
    private function buildIdentities(User $user): array
    {
        $identities = [];

        // User's own email
        $identities[] = [
            'email' => $user->getEmail(),
            'type' => 'primary',
            'key' => $this->wkdHandler->getKey($user->getEmail()),
        ];

        // Non-random alias sources from all domains
        $aliases = $this->aliasRepository->findByUser($user, false, true);
        foreach ($aliases as $alias) {
            $source = $alias->getSource();
            if (null !== $source) {
                $identities[] = [
                    'email' => $source,
                    'type' => 'alias',
                    'key' => $this->wkdHandler->getKey($source),
                ];
            }
        }

        return $identities;
    }

    /**
     * Checks the user is allowed to manage given identity.
     */
    private function canManageIdentity(User $user, string $email): bool
    {
        if ($user->getEmail() === $email) {
            return true;
        }

        $aliases = $this->aliasRepository->findByUser($user, false);

        return array_any($aliases, static fn ($alias) => $alias->getSource() === $email);
    }
}
