<?php

namespace App\Validator\Constraints;

use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class EmailDomainValidator.
 */
class EmailDomainValidator extends ConstraintValidator
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * EmailDomainValidator constructor.
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param User       $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value instanceof User) {
            $name = substr(strrchr($value->getEmail(), '@'), 1);
            $domain = $this->manager->getRepository('App:Domain')->findOneBy(['name' => $name]);

            if (null === $domain) {
                $this->context->addViolation('form.missing-domain');
            }
        }
    }
}
