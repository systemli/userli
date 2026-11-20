<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\User;
use App\Handler\RecoveryHandler;
use DateTimeImmutable;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Attribute\AsTwigFunction;

final readonly class RecoveryExtension
{
    public function __construct(private Security $security)
    {
    }

    /**
     * Start time of a recovery process running for the signed-in user, null if there is none.
     */
    #[AsTwigFunction(name: 'activeRecoveryStartTime')]
    public function activeRecoveryStartTime(): ?DateTimeImmutable
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }

        $recoveryStartTime = $user->getRecoveryStartTime();

        return RecoveryHandler::isInProgress($recoveryStartTime) ? $recoveryStartTime : null;
    }
}
