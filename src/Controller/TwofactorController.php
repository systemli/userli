<?php

namespace App\Controller;

use App\Form\Model\Twofactor;
use App\Form\Model\TwofactorBackupAck;
use App\Form\Model\TwofactorConfirm;
use App\Form\TwofactorBackupAckType;
use App\Form\TwofactorConfirmType;
use App\Form\TwofactorType;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\QrCode\QrCodeGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TwofactorController extends AbstractController
{
    /**
     * @throws \Exception
     */
    public function twofactorAction(Request $request, TotpAuthenticatorInterface $totpAuthenticator): Response
    {
        /** @var $user TwoFactorInterface */
        if (null === $user = $this->getUser()) {
            throw new \Exception('User should not be null');
        }

        $twofactorModel = new Twofactor();
        $form = $this->createForm(TwofactorType::class, $twofactorModel);

        $twofactorConfirmModel = new TwofactorConfirm();
        $confirmForm = $this->createForm(
            TwofactorConfirmType::class,
            $twofactorConfirmModel,
            [
                'action' => $this->generateUrl('user_twofactor_confirm'),
                'method' => 'post',
            ]
        );

        $twofactorDisableModel = new Twofactor();
        $disableForm = $this->createForm(
            TwofactorType::class,
            $twofactorDisableModel,
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
                $this->getDoctrine()->getManager()->flush();

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
     * @throws \Exception
     */
    public function twofactorConfirmAction(Request $request): Response
    {
        if (null === $user = $this->getUser()) {
            throw new \Exception('User should not be null');
        }

        $twofactorConfirmModel = new TwofactorConfirm();
        $confirmForm = $this->createForm(TwofactorConfirmType::class, $twofactorConfirmModel);

        if ('POST' === $request->getMethod()) {
            $confirmForm->handleRequest($request);

            $twofactorModel = new Twofactor();
            $form = $this->createForm(
                TwofactorType::class,
                $twofactorModel,
                [
                    'action' => $this->generateUrl('user_twofactor'),
                    'method' => 'post',
                ]
            );

            $twofactorDisableModel = new Twofactor();
            $disableForm = $this->createForm(
                TwofactorType::class,
                $twofactorDisableModel,
                [
                    'action' => $this->generateUrl('user_twofactor_disable'),
                    'method' => 'post',
                ]
            );

            $twofactorBackupAckModel = new TwofactorBackupAck();
            $backupAckForm = $this->createForm(
                TwofactorBackupAckType::class,
                $twofactorBackupAckModel,
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
     * @throws \Exception
     */
    public function twofactorBackupAckAction(Request $request): Response
    {
        if (null === $user = $this->getUser()) {
            throw new \Exception('User should not be null');
        }

        $twofactorBackupAckModel = new TwofactorBackupAck();
        $backupAckForm = $this->createForm(
            TwofactorBackupAckType::class,
            $twofactorBackupAckModel,
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
                    $this->getDoctrine()->getManager()->flush();

                    return $this->redirectToRoute('user_twofactor');
                } else {
                    $twofactorModel = new Twofactor();
                    $form = $this->createForm(
                        TwofactorType::class,
                        $twofactorModel,
                        [
                            'action' => $this->generateUrl('user_twofactor'),
                            'method' => 'post',
                        ]
                    );

                    $twofactorConfirmModel = new TwofactorConfirm();
                    $confirmForm = $this->createForm(
                        TwofactorConfirmType::class,
                        $twofactorConfirmModel,
                        [
                            'action' => $this->generateUrl('user_twofactor_confirm'),
                            'method' => 'post',
                        ]
                    );

                    $twofactorDisableModel = new Twofactor();
                    $disableForm = $this->createForm(
                        TwofactorType::class,
                        $twofactorDisableModel,
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
     * @throws \Exception
     */
    public function twofactorDisableAction(Request $request): Response
    {
        if (null === $user = $this->getUser()) {
            throw new \Exception('User should not be null');
        }

        $twofactorDisableModel = new Twofactor();
        $disableForm = $this->createForm(TwofactorType::class, $twofactorDisableModel);

        if ('POST' === $request->getMethod()) {
            $disableForm->handleRequest($request);

            if ($disableForm->isSubmitted()) {
                if ($disableForm->isValid()) {
                    $user->setTotpConfirmed(false);
                    $user->setTotpSecret(null);
                    $this->getDoctrine()->getManager()->flush();

                    return $this->redirectToRoute('user_twofactor');
                }

                $twofactorModel = new Twofactor();
                $form = $this->createForm(
                    TwofactorType::class,
                    $twofactorModel,
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
     * @return Response
     *
     * @throws \Exception
     */
    public function displayTotpQrCode(QrCodeGenerator $qrCodeGenerator)
    {
        /** @var $user TwoFactorInterface */
        if (null === $user = $this->getUser()) {
            throw new \Exception('User should not be null');
        }

        $qrCode = $qrCodeGenerator->getTotpQrCode($user);

        return new Response($qrCode->writeString(), 200, ['Content-Type' => 'image/png']);
    }
}
