<?php

namespace App\Validator\Constraints;

use App\Entity\Domain;
use App\Repository\DomainRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DomainValidator extends ConstraintValidator
{
    private DomainRepository $domainRepository;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->domainRepository = $manager->getRepository(Domain::class);
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if ($this->domainRepository->findByName($value) !== null) {
            $this->context->addViolation('form.already-exists');
        }
    }
}
