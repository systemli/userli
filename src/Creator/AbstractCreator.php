<?php

declare(strict_types=1);

namespace App\Creator;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\ReservedName;
use App\Entity\Voucher;
use App\Exception\ValidationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Class AbstractCreator.
 */
abstract class AbstractCreator
{
    /**
     * AbstractCreator constructor.
     */
    public function __construct(private readonly EntityManagerInterface $manager, private readonly ValidatorInterface $validator, protected EventDispatcherInterface $eventDispatcher)
    {
    }

    /**
     * @throws ValidationException
     */
    public function validate(ReservedName|Alias|Domain|Voucher $entity, ?array $validationGroups = null): void
    {
        $violations = $this->validator->validate($entity, null, $validationGroups);

        if ($violations->count() > 0) {
            throw new ValidationException($violations);
        }
    }

    protected function save(ReservedName|Alias|Domain|Voucher $entity): void
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
