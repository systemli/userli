<?php

declare(strict_types=1);

namespace App\Voter;

use App\Entity\Alias;
use App\Entity\User;
use App\Enum\Roles;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Alias>
 */
final class AliasVoter extends Voter
{
    public const string DELETE = 'delete';

    public function __construct(private readonly Security $security)
    {
    }

    #[Override]
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

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        $alias = $subject;

        if (!$user instanceof User || !$alias instanceof Alias || $alias->isDeleted()) {
            return false;
        }

        $isOwner = $alias->getUser() === $user;
        $canDeleteRandom = $alias->isRandom() && $isOwner;
        $canDeleteAsAdmin = $isOwner && ($this->security->isGranted(Roles::ADMIN) || $this->security->isGranted(Roles::DOMAIN_ADMIN));

        return $canDeleteRandom || $canDeleteAsAdmin;
    }
}
