<?php

namespace App\Validator\Constraints;

use App\Entity\Alias;
use App\Repository\AliasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Bundle\SecurityBundle\Security;

class AliasDeleteValidator extends ConstraintValidator
{
    private readonly AliasRepository $repository;

    public function __construct(
        private Security $security,
        private readonly EntityManagerInterface $manager,
    ) {
        $this->security = $security;
        $this->repository = $manager->getRepository(Alias::class);;
    }

    /**
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof AliasDelete) {
            throw new UnexpectedTypeException('Wrong constraint type given', AliasDelete::class);
        }

        if (!$value instanceof Alias) {
            throw new UnexpectedTypeException('Wrong object type given', Alias::class);
        }

        if ($this->security->getUser() != $value->getUser()) {
            $this->context->addViolation('forbidden');
            return;
        }

        if (!$value->isRandom()) {
            $this->context->addViolation('not allowed to delete custom alias. contact your system administrator');
        }
    }
}
