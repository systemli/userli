<?php

namespace App\Controller;

use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\QrCode\QrCodeGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class TwofactorController extends AbstractController
{
	/**
	 * @param TotpAuthenticatorInterface $totpAuthenticator
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function enableTwofactor(TotpAuthenticatorInterface $totpAuthenticator) {
		if (null === $user = $this->getUser()) {
			throw new \Exception('User should not be null');
		}

		if (!$user->isTotpAuthenticationEnabled()) {
			$user->setTotpSecret($totpAuthenticator->generateSecret());
			$this->getDoctrine()->getManager()->flush();
		}

		dd($user);
	}

	/**
	 * @param QrCodeGenerator $qrCodeGenerator
	 *
	 * @return Response
	 * @throws \Exception
	 */
	public function displayTotpQrCode(QrCodeGenerator $qrCodeGenerator) {
		/** @var $user TwoFactorInterface */
		if (null === $user = $this->getUser()) {
			throw new \Exception('User should not be null');
		}

		$qrCode = $qrCodeGenerator->getTotpQrCode($user);

		return new Response($qrCode->writeString(), 200, ['Content-Type' => 'image/png']);
	}
}
