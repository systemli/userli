<?php

namespace App\Validator\Constraints;

use App\Entity\Alias;
use App\Dto\AliasDto;
use App\Repository\AliasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Bundle\SecurityBundle\Security;

class AliasCreateValidator extends ConstraintValidator
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
    public function validate(mixed $object, Constraint $constraint): void
    {
        if (!$constraint instanceof AliasDto) {
            throw new UnexpectedTypeException('Wrong constraint type given', AliasDto::class);
        }

        $user = $this->security->getUser();

        if (!$object) {
            if ($constraint->random_alias_limit <= $this->repository->countByUser($user, true)) {
                $this->context->addViolation('alias-limit-random-reached');
                return;
            }
        }

        if ($constraint->custom_alias_limit <= $this->repository->countByUser($user, false)) {
            $this->context->addViolation('alias-limit-custom-reached');
            return;
        }
    }
}
