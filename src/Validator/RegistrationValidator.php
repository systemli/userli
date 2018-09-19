<?php

namespace App\Validator;

use App\Form\Model\Registration;
use App\Guesser\DomainGuesser;
use App\Repository\AliasRepository;
use App\Repository\UserRepository;
use App\Repository\VoucherRepository;
use App\Repository\ReservedNameRepository;
use App\Validator\Constraints\RegistrationConstraint;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @author louis <louis@systemli.org>
 */
class RegistrationValidator extends ConstraintValidator
{
    /**
     * @var DomainGuesser
     */
    private $domainGuesser;
    /**
     * @var AliasRepository
     */
    private $aliasRepository;
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var VoucherRepository
     */
    private $voucherRepository;
    /**
     * @var ReservedNameRepository
     */
    private $reservedNameRepository;

    /**
     * @var string
     */
    private $domain;

    /**
     * Constructor.
     *
     * @param DomainGuesser $domainGuesser
     * @param ObjectManager $manager
     * @param string        $domain
     */
    public function __construct(DomainGuesser $domainGuesser, ObjectManager $manager, $domain)
    {
        $this->domainGuesser = $domainGuesser;
        $this->aliasRepository = $manager->getRepository('App:Alias');
        $this->userRepository = $manager->getRepository('App:User');
        $this->voucherRepository = $manager->getRepository('App:Voucher');
        $this->reservedNameRepository = $manager->getRepository('App:ReservedName');
        $this->domain = $domain;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param Registration                      $value
     * @param Constraint|RegistrationConstraint $constraint
     *
     * @throws UnexpectedTypeException
     *
     * @return bool
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Registration) {
            throw new UnexpectedTypeException('Wrong value type given', 'App\Form\Model\Registration');
        }

        $hasErrors = false;

        if (null === $voucher = $this->voucherRepository->findByCode($value->getVoucher())) {
            $this->context->addViolation('registration.voucher-invalid');
            $hasErrors = true;
        }

        if (null !== $voucher && $voucher->isRedeemed()) {
            $this->context->addViolation('registration.voucher-already-redeemed');
            $hasErrors = true;
        }

        $name = explode('@', $value->getEmail())[0];
        $domain = explode('@', $value->getEmail())[1];

        if (null !== $this->userRepository->findByEmail($value->getEmail()) ||
            null !== $this->aliasRepository->findBySource($value->getEmail()) ||
            null !== $this->reservedNameRepository->findByName($name)) {
            $this->context->addViolation('registration.email-already-taken');
            $hasErrors = true;
        }

        if (1 !== preg_match('/^[a-z0-9\-\_\.]*$/ui', $name)) {
            $this->context->addViolation('registration.email-unexpected-characters');
            $hasErrors = true;
        }

        if ($domain !== $this->domain) {
            $this->context->addViolation('registration.email-domain-invalid');
            $hasErrors = true;
        }

        if (is_numeric($minLength = $constraint->minLength)) {
            if (strlen($name) < $minLength) {
                $this->context->addViolation('registration.email-too-short', array('%min%' => $constraint->minLength));
                $hasErrors = true;
            }
        }

        if (is_numeric($maxLength = $constraint->maxLength)) {
            if (strlen($name) > $maxLength) {
                $this->context->addViolation('registration.email-too-long', array('%max%' => $constraint->maxLength));
                $hasErrors = true;
            }
        }

        if (null === $this->domainGuesser->guess($value->getEmail())) {
            $this->context->addViolation('registration.email-domain-not-exists');
            $hasErrors = true;
        }

        return !$hasErrors;
    }
}
