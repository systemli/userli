<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\Model\Twofactor;
use App\Form\Model\TwofactorBackupConfirm;
use App\Form\Model\TwofactorConfirm;
use App\Form\TwofactorBackupConfirmType;
use App\Form\TwofactorConfirmType;
use App\Form\TwofactorType;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TwofactorController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
    ) {
    }

    #[Route(path: '/account/twofactor', name: 'account_twofactor', methods: ['GET'])]
    public function show(): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);
        if (!$user->isTotpAuthenticationEnabled()) {
            $form = $this->createForm(TwofactorType::class, new Twofactor(), [
                'action' => $this->generateUrl('account_twofactor_submit'),
                'method' => 'POST',
            ]);

            return $this->render('Account/twofactor_enable.html.twig', [
                'form' => $form,
                'user' => $user,
            ]);
        }

        $form = $this->createForm(TwofactorType::class, new Twofactor(), [
            'action' => $this->generateUrl('account_twofactor_disable'),
            'method' => 'POST',
        ]);
        $form->add('submit', SubmitType::class, ['label' => 'account.twofactor.disable-button']);

        return $this->render('Account/twofactor_disable.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route(path: '/account/twofactor', name: 'account_twofactor_submit', methods: ['POST'])]
    public function submit(Request $request, TotpAuthenticatorInterface $totpAuthenticator): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);
        $form = $this->createForm(TwofactorType::class, new Twofactor());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setTotpSecret($totpAuthenticator->generateSecret());
            $user->generateBackupCodes();
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
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(TwofactorBackupConfirmType::class, new TwofactorBackupConfirm(), [
            'action' => $this->generateUrl('account_twofactor_backup_confirm_submit'),
            'method' => 'POST',
        ]);

        return $this->render('Account/twofactor_backup_code_confirm.html.twig',
            [
                'form' => $form,
                'user' => $user,
            ]
        );
    }

    #[Route(path: '/account/twofactor/backup-codes', name: 'account_twofactor_backup_confirm_submit', methods: ['POST'])]
    public function backupAckSubmit(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(TwofactorBackupConfirmType::class, new TwofactorBackupConfirm());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setTotpConfirmed(true);
            $this->manager->flush();

            return $this->redirectToRoute('account_twofactor');
        }

        return $this->render('Account/twofactor_backup_code_confirm.html.twig',
            [
                'form' => $form,
                'user' => $user,
            ]
        );
    }

    #[Route(path: '/account/twofactor/disable', name: 'account_twofactor_disable', methods: ['POST'])]
    public function disable(Request $request): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);
        $form = $this->createForm(TwofactorType::class, new Twofactor());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setTotpConfirmed(false);
            $user->setTotpSecret(null);
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
