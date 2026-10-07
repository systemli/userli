<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\Model\RecoveryTokenRegenerate;
use App\Form\RecoveryTokenRegenerateType;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use App\Handler\UserAuthenticationHandler;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use LogicException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class RecoveryTokenRegenerateController extends AbstractController
{
    public function __construct(
        private readonly UriSigner $uriSigner,
        private readonly UserRepository $userRepository,
        private readonly UserAuthenticationHandler $authenticationHandler,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
        private readonly MailCryptKeyHandler $mailCryptKeyHandler,
        private readonly RecoveryTokenHandler $recoveryTokenHandler,
        private readonly EntityManagerInterface $manager,
    ) {
    }

    #[Route(
        path: '/recovery/token/regenerate',
        name: 'recovery_token_regenerate',
        methods: ['GET'],
    )]
    public function show(Request $request): Response
    {
        $user = $this->verifyRequestAndLoadUser($request);

        $form = $this->createForm(
            RecoveryTokenRegenerateType::class,
            new RecoveryTokenRegenerate(),
            [
                'action' => $request->getRequestUri(),
                'method' => 'post',
                'requires_totp' => $user->isTotpAuthenticationEnabled(),
            ],
        );

        return $this->render('Recovery/token_regenerate.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route(
        path: '/recovery/token/regenerate',
        name: 'recovery_token_regenerate_submit',
        methods: ['POST'],
    )]
    public function submit(Request $request): Response
    {
        $user = $this->verifyRequestAndLoadUser($request);
        $requiresTotp = $user->isTotpAuthenticationEnabled();

        $data = new RecoveryTokenRegenerate();
        $form = $this->createForm(
            RecoveryTokenRegenerateType::class,
            $data,
            ['requires_totp' => $requiresTotp],
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null === $this->authenticationHandler->authenticate($user, $data->getPassword())) {
                $this->addFlash('error', 'flashes.password-confirmation-failed');

                return $this->render('Recovery/token_regenerate.html.twig', [
                    'form' => $form,
                    'user' => $user,
                ]);
            }

            if ($requiresTotp && !$this->verifyTotp($user, (string) $data->getTotpCode())) {
                $this->addFlash('error', 'flashes.twofactor-code-invalid');

                return $this->render('Recovery/token_regenerate.html.twig', [
                    'form' => $form,
                    'user' => $user,
                ]);
            }

            try {
                $this->regenerateRecoveryToken($user, $data->getPassword());
            } catch (Exception) {
                $this->addFlash('error', 'flashes.password-confirmation-failed');

                return $this->render('Recovery/token_regenerate.html.twig', [
                    'form' => $form,
                    'user' => $user,
                ]);
            }

            if (null === $recoveryToken = $user->getPlainRecoveryToken()) {
                throw new LogicException('plainRecoveryToken should not be null');
            }

            $user->eraseCredentials();
            $this->addFlash('success', 'flashes.recovery-token-regenerated');

            return $this->redirectToRoute('recovery_recovery_token_ack', [
                'recoveryToken' => $recoveryToken,
            ]);
        }

        return $this->render('Recovery/token_regenerate.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    private function verifyRequestAndLoadUser(Request $request): User
    {
        if (!$this->uriSigner->check($request->getRequestUri())) {
            throw new AccessDeniedHttpException('Invalid or expired recovery link.');
        }

        $userId = $request->query->getInt('user');
        if ($userId <= 0) {
            throw new AccessDeniedHttpException('Invalid recovery link.');
        }

        $user = $this->userRepository->find($userId);
        if (!$user instanceof User || $user->isDeleted()) {
            throw new NotFoundHttpException('User not found.');
        }

        return $user;
    }

    private function verifyTotp(User $user, string $code): bool
    {
        if ('' === $code) {
            return false;
        }

        if ($this->totpAuthenticator->checkCode($user, $code)) {
            return true;
        }

        if ($user->isBackupCode($code)) {
            $user->invalidateBackupCode($code);
            $this->manager->flush();

            return true;
        }

        return false;
    }

    /**
     * Rotate the recovery token, re-encrypting the MailCrypt private key when present.
     *
     * @throws Exception on password mismatch against an existing MailCrypt secret box
     */
    private function regenerateRecoveryToken(User $user, string $password): void
    {
        if ($user->hasMailCryptSecretBox()) {
            $user->setPlainMailCryptPrivateKey($this->mailCryptKeyHandler->decrypt($user, $password));
        } else {
            $this->mailCryptKeyHandler->create($user, $password);
        }

        $this->recoveryTokenHandler->create($user);
    }
}
