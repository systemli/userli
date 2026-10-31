<?php

declare(strict_types=1);

namespace App\Controller\Account;

use App\Entity\User;
use App\Form\Model\PasswordConfirmation;
use App\Form\Model\TwofactorBackupConfirm;
use App\Form\Model\TwofactorConfirm;
use App\Form\PasswordConfirmationType;
use App\Form\TwofactorBackupConfirmType;
use App\Form\TwofactorConfirmType;
use App\Repository\WebauthnCredentialRepository;
use App\Service\TwofactorBackupCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TwofactorController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
        private readonly TwofactorBackupCodeService $backupCodeService,
        private readonly WebauthnCredentialRepository $webauthnCredentialRepository,
    ) {
    }

    /**
     * Overview of all second factors and the backup codes.
     */
    #[Route(path: '/account/twofactor', name: 'account_twofactor', methods: ['GET'])]
    public function overview(): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);

        return $this->render('Account/twofactor.html.twig', [
            'user' => $user,
            'webauthn_credentials' => $this->webauthnCredentialRepository->findByUser($user),
        ]);
    }

    #[Route(path: '/account/twofactor/backup-codes/regenerate', name: 'account_twofactor_backup_regenerate', methods: ['POST'])]
    public function regenerateBackupCodes(Request $request): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);
        if (!$user->isTwofactorEnabled()) {
            return $this->redirectToRoute('account_twofactor');
        }

        $form = $this->createForm(PasswordConfirmationType::class, new PasswordConfirmation());
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'flashes.password-confirmation-failed');

            return $this->redirectToRoute('account_twofactor');
        }

        $this->backupCodeService->regenerate($user);
        $this->manager->flush();

        return $this->redirectToRoute('account_twofactor_backup_codes');
    }

    /**
     * Shows new backup codes until the user confirms having stored them.
     */
    #[Route(path: '/account/twofactor/backup-codes/new', name: 'account_twofactor_backup_codes', methods: ['GET'])]
    public function backupCodes(): Response
    {
        if (null === $codes = $this->backupCodeService->getNewCodes()) {
            return $this->redirectToRoute('account_twofactor');
        }

        $form = $this->createForm(TwofactorBackupConfirmType::class, new TwofactorBackupConfirm(), [
            'action' => $this->generateUrl('account_twofactor_backup_codes_submit'),
            'method' => 'POST',
        ]);

        return $this->render('Account/twofactor_backup_code_confirm.html.twig', [
            'form' => $form,
            'backup_codes' => $codes,
        ]);
    }

    #[Route(path: '/account/twofactor/backup-codes/new', name: 'account_twofactor_backup_codes_submit', methods: ['POST'])]
    public function backupCodesSubmit(Request $request): Response
    {
        if (null === $codes = $this->backupCodeService->getNewCodes()) {
            return $this->redirectToRoute('account_twofactor');
        }

        $form = $this->createForm(TwofactorBackupConfirmType::class, new TwofactorBackupConfirm());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->backupCodeService->clearNewCodes();

            return $this->redirectToRoute('account_twofactor');
        }

        return $this->render('Account/twofactor_backup_code_confirm.html.twig', [
            'form' => $form,
            'backup_codes' => $codes,
        ]);
    }

    #[Route(path: '/account/twofactor/totp', name: 'account_twofactor_totp', methods: ['GET'])]
    public function show(): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);
        if (!$user->isTotpAuthenticationEnabled()) {
            $form = $this->createForm(
                PasswordConfirmationType::class,
                new PasswordConfirmation(),
                [
                    'action' => $this->generateUrl('account_twofactor_submit'),
                    'method' => 'POST',
                    'password_label' => 'form.password',
                    'submit_label' => 'account.twofactor.setup-button',
                ],
            );

            return $this->render('Account/twofactor_enable.html.twig', [
                'form' => $form,
                'user' => $user,
            ]);
        }

        $form = $this->createForm(
            PasswordConfirmationType::class,
            new PasswordConfirmation(),
            [
                'action' => $this->generateUrl('account_twofactor_disable'),
                'method' => 'POST',
                'password_label' => 'form.password',
                'submit_label' => 'account.twofactor.disable-button',
            ],
        );

        return $this->render('Account/twofactor_disable.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route(path: '/account/twofactor/totp', name: 'account_twofactor_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);
        $form = $this->createForm(
            PasswordConfirmationType::class,
            new PasswordConfirmation(),
            [
                'password_label' => 'form.password',
                'submit_label' => 'account.twofactor.setup-button',
            ],
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setTotpSecret($this->totpAuthenticator->generateSecret());
            $this->manager->flush();

            return $this->redirectToRoute('account_twofactor_confirm');
        }

        return $this->render('Account/twofactor_enable.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route(path: '/account/twofactor/confirm', name: 'account_twofactor_confirm', methods: ['GET'])]
    public function confirm(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(TwofactorConfirmType::class, new TwofactorConfirm(), [
            'action' => $this->generateUrl('account_twofactor_confirm_submit'),
            'method' => 'POST',
        ]);

        $qrContent = $this->totpAuthenticator->getQRContent($user);
        $builder = new Builder(data: $qrContent, size: 512, margin: 0);

        return $this->render('Account/twofactor_confirm.html.twig',
            [
                'form' => $form,
                'user' => $user,
                'qr_code_data_uri' => $builder->build()->getDataUri(),
            ]
        );
    }

    #[Route(path: '/account/twofactor/confirm', name: 'account_twofactor_confirm_submit', methods: ['POST'])]
    public function confirmSubmit(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(TwofactorConfirmType::class, new TwofactorConfirm());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Backup codes are shared by all second factors, so they are only created for the first one
            if ($user->isTwofactorEnabled()) {
                $user->setTotpConfirmed(true);
                $this->manager->flush();

                return $this->redirectToRoute('account_twofactor');
            }

            $this->backupCodeService->regenerate($user);
            $this->manager->flush();

            return $this->redirectToRoute('account_twofactor_backup_confirm');
        }

        $qrContent = $this->totpAuthenticator->getQRContent($user);
        $builder = new Builder(data: $qrContent, size: 512, margin: 0);

        return $this->render('Account/twofactor_confirm.html.twig',
            [
                'form' => $form,
                'user' => $user,
                'qr_code_data_uri' => $builder->build()->getDataUri(),
            ]
        );
    }

    #[Route(path: '/account/twofactor/backup-codes', name: 'account_twofactor_backup_confirm', methods: ['GET'])]
    public function backupAck(): Response
    {
        // Without codes from this session, the setup has to be started again
        if (null === $codes = $this->backupCodeService->getNewCodes()) {
            return $this->redirectToRoute('account_twofactor');
        }

        $form = $this->createForm(TwofactorBackupConfirmType::class, new TwofactorBackupConfirm(), [
            'action' => $this->generateUrl('account_twofactor_backup_confirm_submit'),
            'method' => 'POST',
        ]);

        return $this->render('Account/twofactor_backup_code_confirm.html.twig',
            [
                'form' => $form,
                'backup_codes' => $codes,
            ]
        );
    }

    #[Route(path: '/account/twofactor/backup-codes', name: 'account_twofactor_backup_confirm_submit', methods: ['POST'])]
    public function backupAckSubmit(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (null === $codes = $this->backupCodeService->getNewCodes()) {
            return $this->redirectToRoute('account_twofactor');
        }

        $form = $this->createForm(TwofactorBackupConfirmType::class, new TwofactorBackupConfirm());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setTotpConfirmed(true);
            $this->manager->flush();
            $this->backupCodeService->clearNewCodes();

            return $this->redirectToRoute('account_twofactor');
        }

        return $this->render('Account/twofactor_backup_code_confirm.html.twig',
            [
                'form' => $form,
                'backup_codes' => $codes,
            ]
        );
    }

    #[Route(path: '/account/twofactor/disable', name: 'account_twofactor_disable', methods: ['POST'])]
    public function disable(Request $request): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);
        $form = $this->createForm(
            PasswordConfirmationType::class,
            new PasswordConfirmation(),
            [
                'password_label' => 'form.password',
                'submit_label' => 'account.twofactor.disable-button',
            ],
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setTotpConfirmed(false);
            $user->setTotpSecret(null);
            if (!$user->isWebauthnAuthenticationEnabled()) {
                $user->setTotpBackupCodes([]);
            }

            $this->manager->flush();

            return $this->redirectToRoute('account_twofactor');
        }

        return $this->render('Account/twofactor_disable.html.twig',
            [
                'form' => $form,
                'user' => $user,
            ]
        );
    }
}
