<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\User;
use App\Enum\Roles;
use App\Service\DomainGuesser;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class EmailDomainAdminValidator extends ConstraintValidator
{
    public function __construct(
        private readonly Security $security,
        private readonly DomainGuesser $domainGuesser,
    ) {
    }

    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof EmailDomainAdmin) {
            throw new UnexpectedTypeException($constraint, EmailDomainAdmin::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        // Full admins can create users in any domain
        if ($this->security->isGranted(Roles::ADMIN)) {
            return;
        }

        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();

            return;
        }

        $targetDomain = $this->domainGuesser->guess($value);

        if (null === $targetDomain) {
            $this->context->buildViolation($constraint->domainNotFoundMessage)
                ->addViolation();

            return;
        }

        $currentDomain = $currentUser->getDomain();

        if ($targetDomain !== $currentDomain) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
