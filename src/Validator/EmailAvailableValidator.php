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

        [$localPart] = explode('@', $value);

        if (
            null !== $this->manager->getRepository(User::class)->findOneBy(['email' => $value])
            || null !== $this->manager->getRepository(Alias::class)->findOneBySource($value, true)
            || null !== $this->manager->getRepository(ReservedName::class)->findByName($localPart)
        ) {
            $this->context->addViolation('registration.email-already-taken');
        }
    }
}
