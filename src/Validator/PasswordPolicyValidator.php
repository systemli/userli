<?php

namespace App\Validator;

use App\Handler\PasswordStrengthHandler;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @author louis <louis@systemli.org>
 */
class PasswordPolicyValidator extends ConstraintValidator
{
    /**
     * @var PasswordStrengthHandler
     */
    private $handler;

    public function __construct(PasswordStrengthHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (empty($value)) {
            return true;
        }

        $errors = $this->handler->validate($value);

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->context->addViolation($error);
            }

            return false;
        }

        return true;
    }
}
