<?php

namespace App\Controller\Api;

use DateTime;
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
    ) {
    }

    #[Route('/api/user/recovery', name: 'set_recovery_token', methods: ['POST'], stateless: true)]
    public function setRecoveryToken(
        #[MapRequestPayload] PasswordDto $request,
        #[CurrentUser] User $user,
    ): JsonResponse {

        $user->setPlainPassword($request->getPassword());

        // Check if user has a MailCrypt key
        if ($user->hasMailCryptSecretBox()) {
            // Decrypt the MailCrypt key
            $user->setPlainMailCryptPrivateKey($this->mailCryptKeyHandler->decrypt($user, $request->getPassword()));
        } else {
            // Create a new MailCrypt key if none existed before
            $this->mailCryptKeyHandler->create($user);
        }

        // Generate a new recovery token and encrypt the MailCrypt key with it
        $this->recoveryTokenHandler->create($user);
        if (null === $recoveryToken = $user->getPlainRecoveryToken()) {
            return $this->json([
                'message' => 'error',
                'message' => 'unknown error occured when resetting token',
            ], 500);
        }

        // Clear sensitive plaintext data from User object
        $user->eraseCredentials();

        return $this->json([
            'message' => 'success',
            'recoveryToken' => $recoveryToken
        ], 200);
    }

    #[Route('/api/recovery', name: 'recovery_get_status', methods: ['GET'], stateless: true)]
    public function getPasswordRecovery(
        #[MapRequestPayload] RecoveryDto $request,
    ): JsonResponse {

        /** @var User $user */
        $user = $this->manager->getRepository(User::class)->findByEmail($request->email);

        $recoveryStartTime = $user->getRecoveryStartTime();
        $recoveryUnlockedTime = $recoveryStartTime->modify('+ 2 days');
        if (null === $recoveryStartTime || new DateTime($this::PROCESS_EXPIRE) >= $recoveryStartTime) {
            return $this->json([
                'status' => 'success',
                'recovery' => 'not started'
            ], 200);
        } elseif (new DateTime($this::PROCESS_DELAY) < $recoveryStartTime) {
            return $this->json([
                'status' => 'success',
                'recovery' =>  'waitin-period',
                'wait-until' => $recoveryUnlockedTime
            ], 200);
        } else {
            return $this->json([
                'status' => 'success',
                'stage' => 'password-reset',
            ], 200);
        }
    }

    /** 
     * TODO: refactor into smaller pieces 
     * TODO: proper response messages
     */
    #[Route('/api/recovery', name: 'recovery_start', methods: ['POST'], stateless: true)]
    public function postPasswordRecovery(
        #[MapRequestPayload] RecoveryDto $request,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->manager->getRepository(User::class)->findByEmail($request->email);

        $recoveryStartTime = $user->getRecoveryStartTime();

        if (null === $recoveryStartTime || new DateTime($this::PROCESS_EXPIRE) >= $recoveryStartTime) {
            // Recovery process gets started
            $user->updateRecoveryStartTime();
            $this->manager->flush();
            $this->eventDispatcher->dispatch(new UserEvent($user), RecoveryProcessEvent::NAME);

            return $this->json([
                'status' => 'success',
                'date' => $user->getRecoveryStartTime()
            ], 200);
        } elseif (new DateTime($this::PROCESS_DELAY) < $recoveryStartTime) {
            return $this->json([
                'status' => 'error',
                'stage' => 'waiting-period',
                'date' => $user->getRecoveryStartTime()
            ], 403);
        } else {
            // Recovery process successful, go on with the form to reset password
            $user->setPlainPassword($request->getNewPassword());
            $this->passwordUpdater->updatePassword($user);

            $mailCryptPrivateKey = $this->recoveryTokenHandler->decrypt($user, $request->lowerCaseRecoveryToken());

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

            return $this->json([
                'message' => 'success',
                'recoveryToken' => $newRecoveryToken
            ], 200);
        }
    }
}
