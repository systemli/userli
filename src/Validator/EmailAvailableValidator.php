<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Alias;
use App\Entity\ReservedName;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class EmailAvailableValidator extends ConstraintValidator
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof EmailAvailable) {
            throw new UnexpectedTypeException($constraint, EmailAvailable::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if ($this->isTaken($value, $constraint)) {
            $this->context->addViolation('registration.email-already-taken');
        }
    }

    private function isTaken(string $value, EmailAvailable $constraint): bool
    {
        if ($constraint->checkUser
            && null !== $this->manager->getRepository(User::class)->findOneBy(['email' => $value])) {
            return true;
        }

        if ($constraint->checkAlias
            && null !== $this->manager->getRepository(Alias::class)->findOneBySource($value, true)) {
            return true;
        }

        if ($constraint->checkReservedName) {
            [$localPart] = explode('@', $value);

            if (null !== $this->manager->getRepository(ReservedName::class)->findByName($localPart)) {
                return true;
            }
        }

        return false;
    }
}
