<?php

namespace App\Voter;

use App\Entity\Alias;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AliasVoter extends Voter
{
    public const DELETE = 'delete';

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
        if (!$user instanceof User) {
            return false;
        }

        $alias = $subject;
        if (!$alias instanceof Alias) {
            return false;
        }

        // Check if the alias is not deleted
        if ($alias->isDeleted()) {
            return false;
        }

        // It is only allowed to delete random aliases
        if (!$alias->isRandom()) {
            return false;
        }

        // Check if the alias belongs to the user
        if ($alias->getUser() !== $user) {
            return false;
        }

        return true;
    }
}
