<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Handler\RecoveryHandler;
use App\Repository\UserRepository;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Signed links that let the account owner cancel a recovery process without signing in.
 *
 * A link is bound to one recovery process (by its start time) and expires with it.
 */
final readonly class RecoveryCancelLinkService
{
    public function __construct(
        private UriSigner $uriSigner,
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * Signed path, without scheme and host, to cancel the user's current recovery process.
     *
     * The host is left out of the signature on purpose: mail links are built from the
     * `app_url` setting, which can differ from the host the request arrives at.
     */
    public function generatePath(User $user): string
    {
        $recoveryStartTime = $user->getRecoveryStartTime();
        if (null === $recoveryStartTime) {
            throw new LogicException('Recovery process has not been started');
        }

        $path = $this->urlGenerator->generate('recovery_cancel', [
            'email' => $user->getEmail(),
            'started' => $recoveryStartTime->getTimestamp(),
        ], UrlGeneratorInterface::ABSOLUTE_PATH);

        return $this->uriSigner->sign($path, RecoveryHandler::getExpireTime($recoveryStartTime));
    }

    /**
     * Returns the user whose recovery process the request may cancel.
     *
     * Returns null if the signature is invalid or expired, or if the link belongs to a
     * recovery process that is no longer running.
     */
    public function resolve(Request $request): ?User
    {
        $uri = $request->getBaseUrl().$request->getPathInfo().'?'.$request->getQueryString();
        if (!$this->uriSigner->check($uri)) {
            return null;
        }

        $user = $this->userRepository->findByEmail($request->query->getString('email'));
        $recoveryStartTime = $user?->getRecoveryStartTime();

        if (null === $user
            || !RecoveryHandler::isInProgress($recoveryStartTime)
            || $recoveryStartTime?->getTimestamp() !== $request->query->getInt('started')
        ) {
            return null;
        }

        return $user;
    }
}
