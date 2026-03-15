<?php

declare(strict_types=1);

namespace App\Voter;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use App\Enum\Roles;
use App\Form\Model\AliasAdminModel;
use App\Form\Model\UserAdminModel;
use App\Service\DomainGuesser;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, User|UserAdminModel|Alias|AliasAdminModel>
 */
final class DomainVoter extends Voter
{
    public const string CREATE = 'create';

    public const string VIEW = 'view';

    public const string EDIT = 'edit';

    public const string DELETE = 'delete';

    private const array SUPPORTED_ATTRIBUTES = [self::CREATE, self::VIEW, self::EDIT, self::DELETE];

    public function __construct(
        private readonly Security $security,
        private readonly DomainGuesser $domainGuesser,
    ) {
    }

    #[Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, self::SUPPORTED_ATTRIBUTES, true)) {
            return false;
        }

        if ($attribute === self::CREATE) {
            return $subject instanceof User || $subject instanceof UserAdminModel || $subject instanceof Alias || $subject instanceof AliasAdminModel;
        }

        if ($attribute === self::EDIT) {
            return $subject instanceof User || $subject instanceof UserAdminModel || $subject instanceof Alias;
        }

        if ($attribute === self::DELETE) {
            return $subject instanceof User;
        }

        return $subject instanceof User || $subject instanceof Alias;
    }

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        // Full admins can do everything
        if ($this->security->isGranted(Roles::ADMIN)) {
            return true;
        }

        // Must be at least domain admin
        if (!$this->security->isGranted(Roles::DOMAIN_ADMIN)) {
            return false;
        }

        // Domain admins cannot manage admin users
        if ($subject instanceof User && $subject->hasRole(Roles::ADMIN)) {
            return false;
        }

        if ($subject instanceof UserAdminModel && in_array(Roles::ADMIN, $subject->getRoles(), true)) {
            return false;
        }

        $currentUser = $token->getUser();
        if (!$currentUser instanceof User) {
            return false;
        }

        $currentDomain = $currentUser->getDomain();

        if (null === $currentDomain) {
            return false;
        }

        if ($subject instanceof User) {
            return $this->voteOnUser($attribute, $subject, $currentDomain);
        }

        if ($subject instanceof UserAdminModel) {
            return $this->voteOnUserAdminModel($subject, $currentDomain);
        }

        if ($subject instanceof Alias) {
            return $this->voteOnAlias($attribute, $subject, $currentDomain);
        }

        if ($subject instanceof AliasAdminModel) {
            return $this->voteOnAliasAdminModel($subject, $currentDomain);
        }

        return false;
    }

    private function voteOnUser(string $attribute, User $user, Domain $currentDomain): bool
    {
        return match ($attribute) {
            self::VIEW, self::DELETE => $currentDomain->getId() === $user->getDomain()?->getId(),
            self::CREATE, self::EDIT => $currentDomain->getId() === $this->domainGuesser->guess($user->getEmail())?->getId(),
            default => false,
        };
    }

    private function voteOnUserAdminModel(UserAdminModel $model, Domain $currentDomain): bool
    {
        return $currentDomain->getId() === $this->domainGuesser->guess($model->getEmail())?->getId();
    }

    private function voteOnAlias(string $attribute, Alias $alias, Domain $currentDomain): bool
    {
        return match ($attribute) {
            self::VIEW => $currentDomain->getId() === $alias->getDomain()?->getId(),
            self::CREATE, self::EDIT => $currentDomain->getId() === $this->domainGuesser->guess($alias->getSource())?->getId(),
            default => false,
        };
    }

    private function voteOnAliasAdminModel(AliasAdminModel $model, Domain $currentDomain): bool
    {
        return $currentDomain->getId() === $this->domainGuesser->guess($model->getSource())?->getId();
    }
}
