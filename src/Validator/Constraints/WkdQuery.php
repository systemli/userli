<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class WkdQuery extends Constraint
{
    public $message = 'Not allowed to access or manipulate';
}
