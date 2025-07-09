<?php

namespace App\Handler;

use App\Entity\User;
use App\Event\UserCreatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

readonly class UserRestoreHandler
{
    public function __construct(
        private EntityManagerInterface    $manager,
        private UserPasswordUpdateHandler $userPasswordUpdateHandler,
        private RecoveryTokenHandler      $recoveryTokenHandler,
        private EventDispatcherInterface  $eventDispatcher,
    )
    {
    }

    public function restoreUser(User $user, string $password): ?string
    {
        $user->setDeleted(false);
        $this->userPasswordUpdateHandler->updatePassword($user, $password);

        // Generate MailCrypt key with new password (overwrites old MailCrypt key)
        $this->recoveryTokenHandler->create($user);
        $recoveryToken = $user->getPlainRecoveryToken();
        // Clear sensitive plaintext data from User object
        $user->eraseCredentials();

        $this->manager->flush();

        $this->eventDispatcher->dispatch(new UserCreatedEvent($user));

        return $recoveryToken;
    }
}
