<?php

namespace App\Controller;

use App\Form\Model\Twofactor;
use App\Form\Model\TwofactorBackupAck;
use App\Form\Model\TwofactorConfirm;
use App\Form\TwofactorBackupAckType;
use App\Form\TwofactorConfirmType;
use App\Form\TwofactorType;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use RuntimeException;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class TwofactorController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    #[Route(path: '/user/twofactor', name: 'user_twofactor', methods: ['GET'])]
    public function show(): Response
    {
        if (!$this->getUser()->isTotpAuthenticationEnabled()) {
            $form = $this->createForm(TwofactorType::class, new Twofactor(), [
                'action' => $this->generateUrl('user_twofactor_submit'),
                'method' => 'POST',
            ]);
            return $this->render('User/twofactor_enable.html.twig', [
                'form' => $form,
                'twofactor_enabled' => false,
            ]);
        }

        $form = $this->createForm(TwofactorType::class, new Twofactor(), [
            'action' => $this->generateUrl('user_twofactor_disable'),
            'method' => 'POST',
        ]);

        return $this->render('User/twofactor_disable.html.twig', [
            'form' => $form,
            'twofactor_enabled' => true,
        ]);
    }

    #[Route(path: '/user/twofactor', name: 'user_twofactor_submit', methods: ['POST'])]
    public function submit(Request $request, TotpAuthenticatorInterface $totpAuthenticator): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(TwofactorType::class, new Twofactor());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setTotpSecret($totpAuthenticator->generateSecret());
            $user->generateBackupCodes();
            $this->manager->flush();

            return $this->redirectToRoute('user_twofactor_confirm');
        }

        return $this->render('User/twofactor_enable.html.twig', [
            'form' => $form,
            'twofactor_enabled' => false,
        ]);
    }

    #[Route(path: '/user/twofactor_confirm', name: 'user_twofactor_confirm', methods: ['GET'])]
    public function confirm(): Response
    {
        $form = $this->createForm(TwofactorConfirmType::class, new TwofactorConfirm(), [
            'action' => $this->generateUrl('user_twofactor_confirm_submit'),
            'method' => 'POST',
        ]);

        return $this->render('User/twofactor_confirm.html.twig',
            [
                'form' => $form,
                'twofactor_enabled' => $this->getUser()->isTotpAuthenticationEnabled(),
            ]
        );
    }

    #[Route(path: '/user/twofactor_confirm', name: 'user_twofactor_confirm_submit', methods: ['POST'])]
    public function confirmSubmit(Request $request): Response
    {
        $form = $this->createForm(TwofactorConfirmType::class, new TwofactorConfirm());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('user_twofactor_backup_ack');
        }

        return $this->render('User/twofactor_confirm.html.twig',
            [
                'form' => $form,
                'twofactor_enabled' => $this->getUser()->isTotpAuthenticationEnabled(),
            ]
        );
    }

    #[Route(path: '/user/twofactor_backup_codes', name: 'user_twofactor_backup_ack', methods: ['GET'])]
    public function backupAck(): Response
    {
        $form = $this->createForm(TwofactorBackupAckType::class, new TwofactorBackupAck(), [
            'action' => $this->generateUrl('user_twofactor_backup_ack_submit'),
            'method' => 'POST',
        ]);

        return $this->render('User/twofactor_backup_ack.html.twig',
            [
                'form' => $form,
                'twofactor_enabled' => $this->getUser()->isTotpAuthenticationEnabled(),
                'twofactor_backup_codes' => $this->getUser()->getBackupCodes(),
            ]
        );
    }

    #[Route(path: '/user/twofactor_backup_codes', name: 'user_twofactor_backup_ack_submit', methods: ['POST'])]
    public function backupAckSubmit(Request $request): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(TwofactorBackupAckType::class, new TwofactorBackupAck());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setTotpConfirmed(true);
            $this->manager->flush();

            return $this->redirectToRoute('user_twofactor');
        }

        return $this->render('User/twofactor_backup_ack.html.twig',
            [
                'form' => $form,
                'twofactor_enabled' => $user->isTotpAuthenticationEnabled(),
                'twofactor_backup_codes' => $user->getBackupCodes(),
            ]
        );
    }

    #[Route(path: '/user/twofactor_disable', name: 'user_twofactor_disable', methods: ['POST'])]
    public function disable(Request $request): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(TwofactorType::class, new Twofactor());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setTotpConfirmed(false);
            $user->setTotpSecret(null);
            $this->manager->flush();

            return $this->redirectToRoute('user_twofactor');
        }

        return $this->render('User/twofactor_disable.html.twig',
            [
                'form' => $form,
                'twofactor_enabled' => $user->isTotpAuthenticationEnabled(),
            ]
        );
    }

    #[Route(path: '/user/twofactor/qrcode', name: 'user_twofactor_qrcode')]
    public function displayTotpQrCode(TotpAuthenticatorInterface $totpAuthenticator): Response
    {
        if (null === $user = $this->getUser()) {
            throw new RuntimeException('User should not be null');
        }

        if (!($user instanceof TwoFactorInterface)) {
            throw new NotFoundHttpException('Cannot display QR code');
        }

        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            data: $totpAuthenticator->getQRContent($user),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 320,
            margin: 20,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );
        $result = $builder->build();

        return new Response($result->getString(), Response::HTTP_OK, ['Content-Type' => 'image/png']);
    }
}
