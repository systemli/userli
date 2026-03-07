<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\AliasEvent;
use App\Event\UserEvent;
use App\Handler\WkdHandler;
use App\Repository\AliasRepository;
use App\Repository\UserRepository;
use Override;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class OpenPgpKeyListener implements EventSubscriberInterface
{
    public function __construct(
        private WkdHandler $wkdHandler,
        private UserRepository $userRepository,
        private AliasRepository $aliasRepository,
    ) {
    }

    public function onCustomAliasDeleted(AliasEvent $event): void
    {
        $source = $event->getAlias()->getSource();

        if (null !== $source) {
            $this->deleteOrphanedOpenPgpKey($source);
        }
    }

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

        $this->wkdHandler->deleteKey($email);
    }

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            AliasEvent::CUSTOM_DELETED => 'onCustomAliasDeleted',
            UserEvent::USER_DELETED => 'onUserDeleted',
        ];
    }
}
