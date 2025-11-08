<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Domain;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EmailDomainValidator extends ConstraintValidator
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof User) {
            return;
        }

        $name = substr(strrchr((string) $value->getEmail(), '@'), 1);
        $domain = $this->manager->getRepository(Domain::class)->findOneBy(['name' => $name]);

        if (null === $domain) {
            $this->context->addViolation('form.missing-domain');
        }
    }
}
