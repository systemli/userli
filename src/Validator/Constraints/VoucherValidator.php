<?php

namespace App\Validator\Constraints;

use App\Repository\VoucherRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Class VoucherValidator.
 *
 * @author doobry <doobry@systemli.org>
 */
class VoucherValidator extends ConstraintValidator
{
    /**
     * @var VoucherRepository
     */
    private $voucherRepository;

    /**
     * EmailAddressValidator constructor.
     *
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->voucherRepository = $manager->getRepository('App:Voucher');
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param string     $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Voucher) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\Voucher');
        }

        if (null === $value || '' === $value) {
            return;
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
        } else {
            if (null !== $voucher = $this->voucherRepository->findByCode($stringValue)) {
                $this->context->addViolation('registration.voucher-exists');
            }
        }
    }
}
