<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Form\Model\RecoveryTokenConfirm;
use App\Form\Model\Registration;
use App\Form\RecoveryTokenConfirmType;
use App\Form\RegistrationType;
use App\Handler\RegistrationHandler;
use App\Repository\UserRepository;
use App\Repository\VoucherRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly RegistrationHandler $registrationHandler,
        private readonly VoucherRepository $voucherRepository,
        private readonly UserRepository $userRepository,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    #[Route(path: '/register', name: 'register', methods: ['GET'])]
    public function redirectToIndex(): Response
    {
        return $this->redirectToRoute('index');
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/register/{voucher}', name: 'register_voucher', requirements: ['voucher' => '[a-zA-Z0-9]{6}'], methods: ['GET'])]
    public function show(string $voucher): Response
    {
        if (!$this->registrationHandler->isRegistrationOpen()) {
            return $this->render('Registration/closed.html.twig');
        }

        $voucherEntity = $this->voucherRepository->findByCode($voucher);

        if (null === $voucherEntity || !$this->isVoucherValid($voucherEntity)) {
            $this->addFlash('error', 'flashes.voucher-invalid');

            return $this->redirectToRoute('index');
        }

        $domainName = $voucherEntity->getDomain()->getName();

        $registration = new Registration();
        $registration->setVoucher($voucher);

        $form = $this->createForm(
            RegistrationType::class,
            $registration,
            [
                'action' => $this->generateUrl('register_submit'),
                'method' => 'post',
                'domain' => $domainName,
            ]
        );

        return $this->render('Registration/register.html.twig', [
            'form' => $form,
            'voucher' => $voucher,
            'registration_domain' => $domainName,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/register', name: 'register_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        if (!$this->registrationHandler->isRegistrationOpen()) {
            return $this->render('Registration/closed.html.twig');
        }

        $registration = new Registration();

        // Extract voucher code from submitted form data to resolve the domain
        $formData = $request->request->all('registration');
        $voucherCode = $formData['voucher'] ?? '';
        $voucherEntity = $this->voucherRepository->findByCode($voucherCode);

        if (null === $voucherEntity || !$this->isVoucherValid($voucherEntity)) {
            $this->addFlash('error', 'flashes.voucher-invalid');

            return $this->redirectToRoute('index');
        }

        $domainName = $voucherEntity->getDomain()->getName();

        $form = $this->createForm(RegistrationType::class, $registration, [
            'domain' => $domainName,
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('Registration/register.html.twig', [
                'form' => $form,
                'voucher' => $registration->getVoucher(),
                'registration_domain' => $domainName,
            ]);
        }

        $this->registrationHandler->handle($registration);
        $user = $this->userRepository->findByEmail($registration->getEmail());
        if (null !== $user) {
            $token = new UsernamePasswordToken($user, 'default', $user->getRoles());
            $this->tokenStorage->setToken($token);
        }

        $recoveryToken = $user->getPlainRecoveryToken();
        // We have fetched plainRecoveryToken, which we need to show and can now remove
        // all sensitive values from the user object
        $user->clearSensitiveData();

        $recoveryTokenAck = new RecoveryTokenConfirm();
        $recoveryTokenAck->setRecoveryToken($recoveryToken);

        $recoveryTokenAckForm = $this->createForm(
            RecoveryTokenConfirmType::class,
            $recoveryTokenAck,
            [
                'action' => $this->generateUrl('register_recovery_token_submit'),
                'method' => 'post',
            ]
        );

        return $this->render('Registration/recovery_token.html.twig',
            [
                'form' => $recoveryTokenAckForm,
                'recovery_token' => $recoveryToken,
            ]
        );
    }

    #[Route(path: '/register/recovery_token', name: 'register_recovery_token_submit', methods: ['POST'])]
    public function submitRecoveryToken(Request $request): Response
    {
        $recoveryTokenAck = new RecoveryTokenConfirm();
        $recoveryTokenAckForm = $this->createForm(
            RecoveryTokenConfirmType::class,
            $recoveryTokenAck
        );

        $recoveryTokenAckForm->handleRequest($request);

        if ($recoveryTokenAckForm->isSubmitted() && $recoveryTokenAckForm->isValid()) {
            return $this->redirectToRoute('register_welcome');
        }

        return $this->render('Registration/recovery_token.html.twig',
            [
                'form' => $recoveryTokenAckForm,
                'recovery_token' => $recoveryTokenAck->getRecoveryToken(),
            ]
        );
    }

    #[Route(path: '/register/welcome', name: 'register_welcome', methods: ['GET'])]
    public function welcome(Request $request): Response
    {
        $this->addFlash('success', 'flashes.registration-successful');

        return $this->render('Registration/welcome.html.twig');
    }

    private function isVoucherValid(Voucher $voucher): bool
    {
        if ($voucher->isRedeemed()) {
            return false;
        }

        /** @var User $user */
        $user = $voucher->getUser();

        return !$user->hasRole(Roles::SUSPICIOUS);
    }
}
