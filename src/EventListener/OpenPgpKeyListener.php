<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\AliasDeletedEvent;
use App\Event\UserEvent;
use App\Repository\AliasRepository;
use App\Repository\UserRepository;
use App\Service\OpenPgpKeyManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class OpenPgpKeyListener
{
    public function __construct(
        private OpenPgpKeyManager $openPgpKeyManager,
        private UserRepository $userRepository,
        private AliasRepository $aliasRepository,
    ) {
    }

    #[AsEventListener(event: AliasDeletedEvent::CUSTOM)]
    public function onCustomAliasDeleted(AliasDeletedEvent $event): void
    {
        $source = $event->getAlias()->getSource();

        if (null !== $source) {
            $this->deleteOrphanedOpenPgpKey($source);
        }
    }

    #[AsEventListener(event: UserEvent::USER_DELETED)]
    public function onUserDeleted(UserEvent $event): void
    {
        $email = $event->getUser()->getEmail();

        if (null !== $email) {
            $this->deleteOrphanedOpenPgpKey($email);
        }
    }

    /**
     * Deletes an OpenPGP key if no non-deleted user or alias still owns the email address.
     */
    private function deleteOrphanedOpenPgpKey(string $email): void
    {
        if ($this->userRepository->existsByEmail($email)) {
            return;
        }

        if (null !== $this->aliasRepository->findOneBySource($email)) {
            return;
        }

        $this->openPgpKeyManager->deleteKey($email);
    }
}
