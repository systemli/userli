<?php

namespace App\Creator;

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
     * @var EntityManagerInterface
     */
    private $manager;
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * AbstractCreator constructor.
     */
    public function __construct(EntityManagerInterface $manager, ValidatorInterface $validator, EventDispatcherInterface $eventDispatcher)
    {
        $this->manager = $manager;
        $this->validator = $validator;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param $entity
     *
     * @throws ValidationException
     */
    protected function validate($entity, array $validationGroups = null): void
    {
        $violations = $this->validator->validate($entity, null, $validationGroups);

        if ($violations->count() > 0) {
            throw new ValidationException($violations);
        }
    }

    /**
     * @param $entity
     */
    protected function save($entity): void
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
