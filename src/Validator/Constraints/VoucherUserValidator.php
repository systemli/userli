<?php

namespace App\Validator\Constraints;

use App\Entity\Voucher;
use App\Enum\Roles;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class VoucherUserValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof VoucherUser) {
            throw new UnexpectedTypeException($constraint, VoucherUser::class);
        }

        if (!$value instanceof Voucher) {
            return;
        }

        $user = $value->getUser();
        if (null !== $user && $user->hasRole(Roles::SUSPICIOUS)) {
            $this->context->addViolation('voucher.suspicious-user');
        }
    }
}
