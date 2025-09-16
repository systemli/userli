<?php

namespace App\Voter;

use App\Entity\Alias;
use App\Entity\User;
use App\Enum\Roles;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AliasVoter extends Voter
{
    public const DELETE = 'delete';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute !== self::DELETE) {
            return false;
        }

        if (!$subject instanceof Alias) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        $alias = $subject; // already ensured to be Alias by supports()

        if (!$user instanceof User || !$alias instanceof Alias) {
            return false; // sanity check
        }

        if ($alias->isDeleted()) {
            return false; // cannot delete already deleted
        }

        $isOwner = $alias->getUser() === $user;

        // owner can delete own random alias
        if ($alias->isRandom() && $isOwner) {
            return true;
        }

        // ADMIN or DOMAIN_ADMIN can delete their own custom aliases
        if ($isOwner && ($this->security->isGranted(Roles::ADMIN) || $this->security->isGranted(Roles::DOMAIN_ADMIN))) {
            return true;
        }

        return false;
    }
}
