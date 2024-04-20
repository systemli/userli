<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Alias;
use App\Dto\PasswordDto;
use App\Dto\WkdDto;
use App\Exception\MultipleGpgKeysForUserException;
use App\Exception\NoGpgDataException;
use App\Exception\NoGpgKeyForUserException;
use App\Entity\OpenPgpKey;
use App\Handler\WkdHandler;
use App\Repository\OpenPgpKeyRepository;
use App\Validator\Constraints\WkdQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use RuntimeException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class WkdController extends AbstractController
{
    private readonly OpenPgpKeyRepository $repository;

    public function __construct(
        private readonly WkdHandler $wkdHandler,
        private readonly EntityManagerInterface $manager,
        private ValidatorInterface $validator,

    ) {
        $this->repository = $manager->getRepository(OpenPgpKey::class);
        $this->validator = $validator;
    }

    #[Route('/api/user/wkd', methods: ['GET'], stateless: true)]
    public function getAllOpenPgpKeys(#[CurrentUser] User $user): JsonResponse
    {
        $allowedUids = $this->getAllowedUserIdByUser($user);

        $keyData = [];
        if (null != $openPgpKeys = $this->repository->findByEmailList($allowedUids)) {
            $keyData = array_map(function (OpenPgpKey $openPgpKey) {
                return $this->printOpenPgpKey($openPgpKey);
            }, $openPgpKeys);
        }

        return $this->json([
            'status' => 'success',
            'allowedUids' => $allowedUids,
            'keyData' => $keyData
        ], 200);
    }

    #[Route('/api/user/wkd/{uid}', methods: ['GET'], stateless: true)]
    public function getOpenPgpKey(string $uid): JsonResponse
    {
        if ($error = $this->validatePgpRequest($uid)) {
            return $this->json(['status' => 'error', 'message' => $error], 403);
        }

        if (null != $openPgpKey = $this->repository->findByEmail($uid)) {
            $keyData = $this->printOpenPgpKey($openPgpKey);

            return $this->json([
                'status' => 'success',
                'keyData' => $keyData
            ], 200);
        }

        return $this->json(['status' => 'error'], 404);
    }

    #[Route('/api/user/wkd/{uid}', methods: ['PUT'], stateless: true)]
    public function putOpenPgpKey(
        #[MapRequestPayload] WkdDto $request,
        #[CurrentUser] User $user,
        string $uid
    ): JsonResponse {

        if ($error = $this->validatePgpRequest($uid)) {
            return $this->json(['status' => 'error', 'message' => $error], 403);
        }

        try {
            $openpgpkey = $this->wkdHandler->importKey($request->keydata, $uid, $user);
        } catch (NoGpgDataException $e) {
            return  $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (NoGpgKeyForUserException $e) {
            return  $this->json(['status' => 'error', 'message' => $e->getMessage()], 403);
        } catch (MultipleGpgKeysForUserException $e) {
            return  $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
        $keyData = $this->printOpenPgpKey($openpgpkey);

        return $this->json([
            'status' => 'success',
            'keyData' => $keyData
        ], 200);
    }

    #[Route('/api/user/wkd/{uid}', methods: ['DELETE'], stateless: true)]
    public function deleteOpenPgpKey(
        #[MapRequestPayload] PasswordDto $request,
        string $uid
    ): JsonResponse {
        if ($error = $this->validatePgpRequest($uid)) {
            return $this->json(['status' => 'error', 'message' => $error], 403);
        }

        try {
            $deleted = $this->wkdHandler->deleteKey($uid);
        } catch (RuntimeException $e) {
            return $this->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }

        if (!$deleted) {
            return $this->json(['status' => 'error', 'message' => 'ressource does not exists'], 404);
        }

        return $this->json(['status' => 'success'], 200);
    }

    /**
     * get non-random mail handles owned by a user.
     */
    public function getAllowedUserIdByUser(User $user): array
    {
        $aliasSources = [];
        if ($aliases = $this->manager->getRepository(Alias::class)->findByUser($user, false, false)) {
            $aliasSources = array_map(function (Alias $alias) {
                return $alias->getSource();
            }, $aliases);
        }
        return array_merge($aliasSources, [$user->getEmail()]);
    }

    /**
     * check if current user is allowed to access uid.
     */
    public function validatePgpRequest(string $uid): ?string
    {
        $violations = $this->validator->validate($uid, new WkdQuery());
        if (count($violations) > 0) {
            $message = "";
            foreach ($violations as $violation) {
                $message .= $violation->getMessage() . '\n';
            }
            return $message;
        }
        return null;
    }

    public function printOpenPgpKey(OpenPgpKey $openPgpKey): array
    {
        if ($uploadedby = $openPgpKey->getUser()) {
            $uploadedby = $uploadedby->__toString();
        } else {
            $uploadedby = null;
        }
        return [
            'userId' => $openPgpKey->getEmail(),
            'keyId' => $openPgpKey->getKeyId(),
            'fingerprint' => $openPgpKey->getKeyFingerprint(),
            'expireTime' => $openPgpKey->getKeyExpireTime(),
            'uploadedBy' =>  $uploadedby
        ];
    }
}
