<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class EmailDomainValidator extends ConstraintValidator
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof EmailDomain) {
            throw new UnexpectedTypeException($constraint, EmailDomain::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        [, $domain] = explode('@', $value);

        if (null === $this->manager->getRepository(Domain::class)->findByName($domain)) {
            $this->context->addViolation('registration.email-domain-not-exists');
        }
    }
}
