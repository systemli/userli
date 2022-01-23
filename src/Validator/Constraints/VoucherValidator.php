<?php

namespace App\Validator\Constraints;

use App\Repository\VoucherRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Class VoucherValidator.
 */
class VoucherValidator extends ConstraintValidator
{
    /**
     * @var VoucherRepository
     */
    private $voucherRepository;

    /**
     * VoucherValidator constructor.
     */
    public function __construct(ObjectManager $manager)
    {
        $this->voucherRepository = $manager->getRepository('App:Voucher');
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed      $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Voucher) {
            throw new UnexpectedTypeException($constraint, Voucher::class);
        }

        if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $stringValue = (string) $value;

        if (true === $constraint->exists) {
            if (null === $voucher = $this->voucherRepository->findByCode($stringValue)) {
                $this->context->addViolation('registration.voucher-invalid');
            }

            if (null !== $voucher && $voucher->isRedeemed()) {
                $this->context->addViolation('registration.voucher-already-redeemed');
            }
        } elseif (null !== $this->voucherRepository->findByCode($stringValue)) {
            $this->context->addViolation('registration.voucher-exists');
        }
    }
}
