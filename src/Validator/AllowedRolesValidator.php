<?php

declare(strict_types=1);

namespace App\Validator;

use App\Enum\Roles;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class AllowedRolesValidator extends ConstraintValidator
{
    public function __construct(private readonly Security $security)
    {
    }

    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof AllowedRoles) {
            throw new UnexpectedTypeException($constraint, AllowedRoles::class);
        }

        if (null === $value || [] === $value) {
            return;
        }

        if (!is_array($value)) {
            throw new UnexpectedTypeException($value, 'array');
        }

        $highestRole = $this->security->isGranted(Roles::ADMIN) ? [Roles::ADMIN] : [Roles::DOMAIN_ADMIN];
        $allowedRoles = Roles::getReachableRoles($highestRole);

        foreach ($value as $role) {
            if (!in_array($role, $allowedRoles, true)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ role }}', (string) $role)
                    ->addViolation();
            }
        }
    }
}
