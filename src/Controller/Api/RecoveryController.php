<?php

namespace App\Controller\Api\User;

use DateTimeImmutable;
use Exception;
use App\Dto\PasswordDto;
use App\Dto\RecoveryDto;
use App\Event\RecoveryProcessEvent;
use App\Event\UserEvent;
use App\Entity\User;
use App\Helper\PasswordUpdater;
use App\Handler\MailCryptKeyHandler;
use App\Handler\RecoveryTokenHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class RecoveryController extends AbstractController
{

    private const PROCESS_DELAY = '-2 days';
    private const PROCESS_EXPIRE = '-30 days';

    public function __construct(
        private readonly MailCryptKeyHandler      $mailCryptKeyHandler,
        private readonly RecoveryTokenHandler     $recoveryTokenHandler,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EntityManagerInterface   $manager,
        private readonly PasswordUpdater          $passwordUpdater,
    ) {}

    #[Route('/api/user/recovery', name: 'set_recovery_token', methods: ['POST'], stateless: true)]
    public function setRecoveryToken(
        #[MapRequestPayload] PasswordDto $request,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $user->setPlainPassword($request->getPassword());

        if ($user->hasMailCryptSecretBox()) {
            $user->setPlainMailCryptPrivateKey($this->mailCryptKeyHandler->decrypt($user, $request->getPassword()));
        } else {
            $this->mailCryptKeyHandler->create($user);
        }

        // Generate a new recovery token and encrypt the MailCrypt key with it
        $this->recoveryTokenHandler->create($user);
        if (null === $recoveryToken = $user->getPlainRecoveryToken()) {
            return $this->json([
                'status' => 'error',
                'message' => 'unknown error occured when resetting token',
            ], 500);
        }

        // Clear sensitive plaintext data from User object
        $user->eraseCredentials();

        return $this->json([
            'status' => 'success',
            'recoveryToken' => $recoveryToken
        ], 200);
    }

    #[Route('/api/recovery', name: 'recovery_get_status', methods: ['GET'], stateless: true)]
    public function getPasswordRecovery(
        #[MapQueryParameter] string $email = '',

    ): JsonResponse {
        // failure to get user does not return 404 in order not to allow user enumeration
        if (null === $user = $this->manager->getRepository(User::class)->findByEmail($email)) {
            $recoveryStartTime = null;
        } else {
            $recoveryStartTime = $user->getRecoveryStartTime();
        }

        if (null === $recoveryStartTime || $this->getProcessExpireTime() >= $recoveryStartTime) {
            return $this->json([
                'status' => 'success',
                'stage' => 'no-started',
                'start-time' => null
            ], 200);
        }

        if ($this->getProcessDelayTime() < $recoveryStartTime) {
            return $this->json([
                'status' => 'success',
                'stage' =>  'waiting-period',
                'start-time' => $recoveryStartTime
            ], 200);
        }

        return $this->json([
            'status' => 'success',
            'stage' => 'password-reset',
            'start-time' => null
        ], 200);
    }

    #[Route('/api/recovery', name: 'recovery_start', methods: ['POST'], stateless: true)]
    public function postPasswordRecovery(
        #[MapRequestPayload] RecoveryDto $request,
    ): JsonResponse {
        $user = $this->manager->getRepository(User::class)->findByEmail($request->email);

        $recoveryStartTime = $user->getRecoveryStartTime();

        if (null === $recoveryStartTime || $this->getProcessDelayTime() >= $recoveryStartTime) {
            $recoveryStartTime = $this->startPasswordRecoveryProcess($user);
            return $this->json([
                'status' => 'success',
                'start-time' => $recoveryStartTime
            ], 200);
        }

        if ($this->getProcessDelayTime() < $recoveryStartTime) {
            return $this->json([
                'status' => 'error',
                'stage' => 'waiting-period',
                'start-time' => $user->getRecoveryStartTime()
            ], 403);
        }

        try {
            $newRecoveryToken = $this->finishPasswordRecoveryProcess($user, $request->getRecoveryToken(), $request->getNewPassword());
        } catch (Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }


        return $this->json([
            'status' => 'success',
            'recoveryToken' => $newRecoveryToken
        ], 200);
    }


    private function startPasswordRecoveryProcess(User $user): DateTimeImmutable
    {
        $user->updateRecoveryStartTime();
        $this->manager->flush();
        $this->eventDispatcher->dispatch(new UserEvent($user), RecoveryProcessEvent::NAME);
        return $user->getRecoveryStartTime();
    }

    private function finishPasswordRecoveryProcess(User $user, string $recoveryToken, string $newPassword): string
    {
        $user->setPlainPassword($newPassword);
        $this->passwordUpdater->updatePassword($user);

        $mailCryptPrivateKey = $this->recoveryTokenHandler->decrypt($user, $recoveryToken);

        // Encrypt MailCrypt private key from recoverySecretBox with new password
        $this->mailCryptKeyHandler->updateWithPrivateKey($user, $mailCryptPrivateKey);

        // Clear old token
        $user->eraseRecoveryStartTime();
        $user->eraseRecoverySecretBox();

        // Generate new token
        $user->setPlainMailCryptPrivateKey($mailCryptPrivateKey);
        $this->recoveryTokenHandler->create($user);
        if (null === $newRecoveryToken = $user->getPlainRecoveryToken()) {
            throw new Exception('PlainRecoveryToken should not be null');
        }

        // Clear sensitive plaintext data from User object
        $user->eraseCredentials();
        sodium_memzero($mailCryptPrivateKey);
        $this->manager->flush();

        return $newRecoveryToken;
    }

    private function getProcessDelayTime(): DateTimeImmutable
    {
        return new DateTimeImmutable(self::PROCESS_DELAY);
    }


    private function getProcessExpireTime(): DateTimeImmutable
    {
        return new DateTimeImmutable(self::PROCESS_EXPIRE);
    }
}
