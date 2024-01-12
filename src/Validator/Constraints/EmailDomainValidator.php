<?php

namespace App\Validator\Constraints;

use App\Entity\Domain;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class EmailDomainValidator.
 */
class EmailDomainValidator extends ConstraintValidator
{
    /**
     * EmailDomainValidator constructor.
     */
    public function __construct(private EntityManagerInterface $manager)
    {
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param User       $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint): void
    {
        if ($value instanceof User) {
            $name = substr(strrchr($value->getEmail(), '@'), 1);
            $domain = $this->manager->getRepository(Domain::class)->findOneBy(['name' => $name]);

            if (null === $domain) {
                $this->context->addViolation('form.missing-domain');
            }
        }
    }
}
