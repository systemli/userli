<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @author louis <louis@systemli.org>
 */
class RegistrationConstraint extends Constraint
{
    /**
     * @var array
     */
    public $domains = array();

    /**
     * @var int|null
     */
    public $minLength;

    /**
     * @var int|null
     */
    public $maxLength;

    public function validatedBy()
    {
        return 'registration';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
