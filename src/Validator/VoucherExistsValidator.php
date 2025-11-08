<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\User;
use App\Entity\Voucher;
use App\Enum\Roles;
use App\Repository\VoucherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class VoucherExistsValidator extends ConstraintValidator
{
    private readonly VoucherRepository $voucherRepository;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->voucherRepository = $manager->getRepository(Voucher::class);
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof VoucherExists) {
            throw new UnexpectedTypeException($constraint, VoucherExists::class);
        }

        if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $stringValue = (string) $value;

        if (true === $constraint->exists) {
            if (null === $voucher = $this->voucherRepository->findByCode($stringValue)) {
                $this->context->addViolation('registration.voucher-invalid');

                return;
            }

            if ($voucher->isRedeemed()) {
                $this->context->addViolation('registration.voucher-already-redeemed');
            }

            /** @var User $user */
            $user = $voucher->getUser();
            if ($user->hasRole(Roles::SUSPICIOUS)) {
                $this->context->addViolation('registration.voucher-invalid');
            }
        } elseif (null !== $this->voucherRepository->findByCode($stringValue)) {
            $this->context->addViolation('registration.voucher-exists');
        }
    }
}
