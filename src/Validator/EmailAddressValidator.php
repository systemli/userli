<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\ReservedName;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class EmailAddressValidator extends ConstraintValidator
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
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
        $user = $this->manager->getRepository(User::class)->findOneBy(['email' => $value], null, true);
        $alias = $this->manager->getRepository(Alias::class)->findOneBySource($value, true);
        $reservedName = $this->manager->getRepository(ReservedName::class)->findByName($localPart);

        if (null !== $user || null !== $alias || null !== $reservedName) {
            $this->context->addViolation('registration.email-already-taken');
        }

        if (1 !== preg_match('/^[a-z0-9\-_.]*$/ui', $localPart)) {
            $this->context->addViolation('registration.email-unexpected-characters');
        }

        // check if email domain is in domain repository
        if (null === $this->manager->getRepository(Domain::class)->findByName($domain)) {
            $this->context->addViolation('registration.email-domain-not-exists');
        }
    }
}
