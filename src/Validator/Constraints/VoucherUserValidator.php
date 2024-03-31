<?php

namespace App\Validator\Constraints;

use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class VoucherUserValidator extends ConstraintValidator
{
    /**
     * Checks if the passed value is valid.
     *
     * @param mixed      $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof VoucherUser) {
            throw new UnexpectedTypeException($constraint, VoucherUser::class);
        }

        if (!$value instanceof Voucher) {
            return;
        }

        /** @var User $user */
        $user = $value->getUser();
        if ($user->hasRole(Roles::SUSPICIOUS)) {
            $this->context->addViolation('voucher.suspicious-user');
        }
    }
}
