<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * @author doobry <doobry@systemli.org>
 */
class EmailAddress extends Constraint
{
    /**
     * @var int|null
     */
    public $minLength;

    /**
     * @var int|null
     */
    public $maxLength;

    /**
     * EmailAddress constructor.
     * @param null $options
     */
    public function __construct($options = null)
    {
        parent::__construct($options);

        if (null === $this->minLength && null === $this->maxLength) {
            throw new MissingOptionsException(sprintf('Either option "minLength" or "maxLength" must be given for constraint %s', __CLASS__), array('min', 'max'));
        }
    }
}
