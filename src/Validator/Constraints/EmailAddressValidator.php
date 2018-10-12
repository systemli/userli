<?php

namespace App\Validator\Constraints;

use App\Repository\AliasRepository;
use App\Repository\DomainRepository;
use App\Repository\UserRepository;
use App\Repository\ReservedNameRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Class EmailAddressValidator.
 * @author doobry <doobry@systemli.org>
 */
class EmailAddressValidator extends ConstraintValidator
{
    /**
     * @var AliasRepository
     */
    private $aliasRepository;
    /**
     * @var DomainRepository
     */
    private $domainRepository;
    /**
     * @var ReservedNameRepository
     */
    private $reservedNameRepository;
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var string
     */
    private $domain;

    /**
     * EmailAddressValidator constructor.
     * @param ObjectManager $manager
     * @param string $domain
     */
    public function __construct(ObjectManager $manager, string $domain)
    {
        $this->aliasRepository = $manager->getRepository('App:Alias');
        $this->domainRepository = $manager->getRepository('App:Domain');
        $this->reservedNameRepository = $manager->getRepository('App:ReservedName');
        $this->userRepository = $manager->getRepository('App:User');
        $this->domain = $domain;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param string     $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof EmailAddress) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\EmailAddress');
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $localPart = explode('@', $value)[0];
        $domain = explode('@', $value)[1];

        if (null !== $this->userRepository->findByEmail($value)) {
            $this->context->addViolation('registration.email-already-taken');
        } elseif (null !== $this->aliasRepository->findOneBySource($value)) {
            $this->context->addViolation('registration.email-already-taken');
        } elseif (null !== $this->reservedNameRepository->findByName($localPart)) {
            $this->context->addViolation('registration.email-already-taken');
        }

        if (1 !== preg_match('/^[a-z0-9\-\_\.]*$/ui', $localPart)) {
            $this->context->addViolation('registration.email-unexpected-characters');
        }

        if (null === $this->domainRepository->findByName($domain)) {
            $this->context->addViolation('registration.email-domain-not-exists');
        } elseif ($domain !== $this->domain) {
            $this->context->addViolation('registration.email-domain-invalid');
        }
    }
}
