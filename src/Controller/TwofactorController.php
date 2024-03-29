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
use Symfony\Component\Routing\Annotation\Route;

class TwofactorController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    /**
     * @param Request $request
     * @param TotpAuthenticatorInterface $totpAuthenticator
     * @return Response
     * @throws RuntimeException
     */
    #[Route(path: '/user/twofactor', name: 'user_twofactor')]
    public function twofactor(Request $request, TotpAuthenticatorInterface $totpAuthenticator): Response
    {
        /** @var $user TwoFactorInterface */
        if (null === $user = $this->getUser()) {
            throw new RuntimeException('User should not be null');
        }

        $form = $this->createForm(TwofactorType::class, new Twofactor());

        $confirmForm = $this->createForm(
            TwofactorConfirmType::class,
            new TwofactorConfirm(),
            [
                'action' => $this->generateUrl('user_twofactor_confirm'),
                'method' => 'post',
            ]
        );

        $disableForm = $this->createForm(
            TwofactorType::class,
            new Twofactor(),
            [
                'action' => $this->generateUrl('user_twofactor_disable'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            $disableForm->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $user->setTotpSecret($totpAuthenticator->generateSecret());
                $user->generateBackupCodes();
                $this->manager->flush();

                return $this->render('User/twofactor.html.twig',
                    [
                        'form' => $form->createView(),
                        'confirmForm' => $confirmForm->createView(),
                        'disableForm' => $disableForm->createView(),
                        'twofactor_enable' => true,
                        'twofactor_enabled' => $user->isTotpAuthenticationEnabled(),
                    ]
                );
            }
        }

        return $this->render('User/twofactor.html.twig',
            [
                'form' => $form->createView(),
                'confirmForm' => $confirmForm->createView(),
                'disableForm' => $disableForm->createView(),
                'twofactor_enable' => false,
                'twofactor_enabled' => $user->isTotpAuthenticationEnabled(),
            ]
        );
    }

    /**
     * @param Request $request
     * @return Response
     * @throws RuntimeException
     */
    #[Route(path: '/user/twofactor_confirm', name: 'user_twofactor_confirm')]
    public function twofactorConfirm(Request $request): Response
    {
        if (null === $user = $this->getUser()) {
            throw new RuntimeException('User should not be null');
        }

        $confirmForm = $this->createForm(TwofactorConfirmType::class, new TwofactorConfirm());

        if ('POST' === $request->getMethod()) {
            $confirmForm->handleRequest($request);

            $form = $this->createForm(
                TwofactorType::class,
                new Twofactor(),
                [
                    'action' => $this->generateUrl('user_twofactor'),
                    'method' => 'post',
                ]
            );

            $disableForm = $this->createForm(
                TwofactorType::class,
                new Twofactor(),
                [
                    'action' => $this->generateUrl('user_twofactor_disable'),
                    'method' => 'post',
                ]
            );

            $backupAckForm = $this->createForm(
                TwofactorBackupAckType::class,
                new TwofactorBackupAck(),
                [
                    'action' => $this->generateUrl('user_twofactor_backup_ack'),
                    'method' => 'post',
                ]
            );

            if ($confirmForm->isSubmitted()) {
                if ($confirmForm->isValid()) {
                    return $this->render('User/twofactor.html.twig',
                        [
                            'form' => $form->createView(),
                            'confirmForm' => $confirmForm->createView(),
                            'backupAckForm' => $backupAckForm->createView(),
                            'disableForm' => $disableForm->createView(),
                            'twofactor_enable' => true,
                            'twofactor_enabled' => $user->isTotpAuthenticationEnabled(),
                            'twofactor_backup_codes' => $user->getBackupCodes(),
                        ]
                    );
                }

                // Again render form to display form errors
                return $this->render('User/twofactor.html.twig',
                    [
                        'form' => $form->createView(),
                        'confirmForm' => $confirmForm->createView(),
                        'backupAckForm' => $backupAckForm->createView(),
                        'disableForm' => $disableForm->createView(),
                        'twofactor_enable' => true,
                        'twofactor_enabled' => $user->isTotpAuthenticationEnabled(),
                    ]
                );
            }
        }

        return $this->redirectToRoute('user_twofactor');
    }

    /**
     * @param Request $request
     * @return Response
     * @throws RuntimeException
     */
    #[Route(path: '/user/twofactor_backup_codes', name: 'user_twofactor_backup_ack')]
    public function twofactorBackupAck(Request $request): Response
    {
        if (null === $user = $this->getUser()) {
            throw new RuntimeException('User should not be null');
        }

        $backupAckForm = $this->createForm(
            TwofactorBackupAckType::class,
            new TwofactorBackupAck(),
            [
                'action' => $this->generateUrl('user_twofactor_backup_ack'),
                'method' => 'post',
            ]
        );

        if ('POST' === $request->getMethod()) {
            $backupAckForm->handleRequest($request);

            if ($backupAckForm->isSubmitted()) {
                if ($backupAckForm->isValid()) {
                    $user->setTotpConfirmed(true);
                    $this->manager->flush();

                    return $this->redirectToRoute('user_twofactor');
                } else {
                    $form = $this->createForm(
                        TwofactorType::class,
                        new Twofactor(),
                        [
                            'action' => $this->generateUrl('user_twofactor'),
                            'method' => 'post',
                        ]
                    );

                    $confirmForm = $this->createForm(
                        TwofactorConfirmType::class,
                        new TwofactorConfirm(),
                        [
                            'action' => $this->generateUrl('user_twofactor_confirm'),
                            'method' => 'post',
                        ]
                    );

                    $disableForm = $this->createForm(
                        TwofactorType::class,
                        new Twofactor(),
                        [
                            'action' => $this->generateUrl('user_twofactor_disable'),
                            'method' => 'post',
                        ]
                    );

                    return $this->render('User/twofactor.html.twig',
                        [
                            'form' => $form->createView(),
                            'confirmForm' => $confirmForm->createView(),
                            'backupAckForm' => $backupAckForm->createView(),
                            'disableForm' => $disableForm->createView(),
                            'twofactor_enable' => true,
                            'twofactor_enabled' => $user->isTotpAuthenticationEnabled(),
                            'twofactor_backup_codes' => $user->getBackupCodes(),
                        ]
                    );
                }
            }
        }

        return $this->redirectToRoute('user_twofactor');
    }

    /**
     * @param Request $request
     * @return Response
     * @throws RuntimeException
     */
    #[Route(path: '/user/twofactor_disable', name: 'user_twofactor_disable')]
    public function twofactorDisable(Request $request): Response
    {
        if (null === $user = $this->getUser()) {
            throw new RuntimeException('User should not be null');
        }

        $disableForm = $this->createForm(TwofactorType::class, new Twofactor());

        if ('POST' === $request->getMethod()) {
            $disableForm->handleRequest($request);

            if ($disableForm->isSubmitted()) {
                if ($disableForm->isValid()) {
                    $user->setTotpConfirmed(false);
                    $user->setTotpSecret(null);
                    $this->manager->flush();

                    return $this->redirectToRoute('user_twofactor');
                }

                $form = $this->createForm(
                    TwofactorType::class,
                    new Twofactor(),
                    [
                        'action' => $this->generateUrl('user_twofactor'),
                        'method' => 'post',
                    ]
                );

                // Again render form to display form errors
                return $this->render('User/twofactor.html.twig',
                    [
                        'form' => $form->createView(),
                        'disableForm' => $disableForm->createView(),
                        'twofactor_enable' => false,
                        'twofactor_enabled' => $user->isTotpAuthenticationEnabled(),
                    ]
                );
            }
        }

        return $this->redirectToRoute('user_twofactor');
    }

    /**
     * @param TotpAuthenticatorInterface $totpAuthenticator
     * @return Response
     * @throws RuntimeException
     */
    #[Route(path: '/user/twofactor/qrcode', name: 'user_twofactor_qrcode')]
    public function displayTotpQrCode(TotpAuthenticatorInterface $totpAuthenticator): Response
    {
        if (null === $user = $this->getUser()) {
            throw new RuntimeException('User should not be null');
        }
        if (!($user instanceof TwoFactorInterface)) {
            throw new NotFoundHttpException('Cannot display QR code');
        }

        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($totpAuthenticator->getQRContent($user))
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(320)
            ->margin(20)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        return new Response($result->getString(), Response::HTTP_OK, ['Content-Type' => 'image/png']);
    }
}
