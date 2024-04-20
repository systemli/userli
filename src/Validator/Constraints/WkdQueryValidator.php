<?php

namespace App\Validator\Constraints;

use App\Entity\User;
use App\Entity\Alias;
use App\DTO\WkdDto;
use App\Repository\AliasRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;

class WkdQueryValidator extends ConstraintValidator
{
    private readonly AliasRepository $aliasRepository;

    public function __construct(
        private Security $security,
        private readonly EntityManagerInterface $manager,
    ) {
        $this->security = $security;
        $this->aliasRepository = $manager->getRepository(Alias::class);
    }

    /**
     * Validate if value matches either the username or an alias source of the requesting user
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof WkdQuery) {
            throw new UnexpectedTypeException($constraint, WkdQuery::class);
        }
        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        /** @var User */
        $user = $this->security->getUser();
        if (!$user) {
            throw new UnexpectedValueException(null, User::class);
        }

        // pass if value matches user email
        if ($value === $user->getEmail()) {
            return;
        }
        // pass if value matches any of users non random aliases
        if ($this->aliasRepository->findOneByUserAndSource($user, $value, false)) {
            return;
        }
        // Add violation in any other case
        return $this->context->buildViolation($constraint->message)->addViolation();
    }
}
