<?php

namespace App\Validator\Constraints;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\ReservedName;
use App\Entity\User;
use App\Repository\AliasRepository;
use App\Repository\DomainRepository;
use App\Repository\ReservedNameRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Class EmailAddressValidator.
 */
class EmailAddressValidator extends ConstraintValidator
{
    private AliasRepository $aliasRepository;
    private DomainRepository $domainRepository;
    private ReservedNameRepository $reservedNameRepository;
    private UserRepository $userRepository;

    /**
     * EmailAddressValidator constructor.
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->aliasRepository = $manager->getRepository(Alias::class);
        $this->domainRepository = $manager->getRepository(Domain::class);
        $this->reservedNameRepository = $manager->getRepository(ReservedName::class);
        $this->userRepository = $manager->getRepository(User::class);
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param string     $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof EmailAddress) {
            throw new UnexpectedTypeException($constraint, EmailAddress::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        [$localPart, $domain] = explode('@', $value);

        if (null !== $this->userRepository->findOneBy(['email' => $value], null, true)) {
            $this->context->addViolation('registration.email-already-taken');
        } elseif (null !== $this->aliasRepository->findOneBySource($value, true)) {
            $this->context->addViolation('registration.email-already-taken');
        } elseif (null !== $this->reservedNameRepository->findByName($localPart)) {
            $this->context->addViolation('registration.email-already-taken');
        }

        if (1 !== preg_match('/^[a-z0-9\-\_\.]*$/ui', $localPart)) {
            $this->context->addViolation('registration.email-unexpected-characters');
        }

        // check if email domain is in domain repository
        if (null === $this->domainRepository->findByName($domain)) {
            $this->context->addViolation('registration.email-domain-not-exists');
        }
    }
}
