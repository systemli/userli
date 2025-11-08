<?php

declare(strict_types=1);

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
        $alias = $subject;

        $isUserValid = $user instanceof User;
        $isAliasValid = $alias instanceof Alias;
        $isNotDeleted = $isAliasValid && !$alias->isDeleted();
        $isRandom = $isAliasValid && $alias->isRandom();
        $isOwner = $isAliasValid && $alias->getUser() === $user;

        return $isUserValid
            && $isAliasValid
            && $isNotDeleted
            && $isRandom
            && $isOwner;
    }
}
