<?php

declare(strict_types=1);

namespace App\Validator;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class TotpSecretValidator extends ConstraintValidator
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage, private readonly TotpAuthenticatorInterface $totpAuthenticator)
    {
    }

    public function validate($value, Constraint $constraint): bool
    {
        if (!$constraint instanceof TotpSecret) {
            throw new UnexpectedTypeException($constraint, TotpSecret::class);
        }

        if (null === $value || '' === $value) {
            return true;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        /** @var \Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface $user */
        $user = $this->tokenStorage->getToken()->getUser();

        if (!$this->totpAuthenticator->checkCode($user, $value)) {
            $this->context->buildViolation('form.twofactor-secret-invalid')
                ->addViolation();

            return false;
        }

        return true;
    }
}
