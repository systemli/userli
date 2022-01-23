<?php

namespace App\Repository;

use App\Entity\SoftDeletableInterface;
use Doctrine\ORM\EntityRepository;

/**
 * Class AbstractRepository.
 */
class AbstractRepository extends EntityRepository
{
    /**
     * {@inheritdoc}
     */
    public function find($id, $lockMode = null, $lockVersion = null, bool $deleted = false)
    {
        $entity = parent::find($id, $lockMode, $lockVersion);
        if (($entity instanceof SoftDeletableInterface) && false === $deleted && $entity->isDeleted()) {
            return null;
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(bool $deleted = false): array
    {
        return $this->findBy([], null, null, null, $deleted);
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null, bool $deleted = false): array
    {
        if (false === $deleted && $this->getClassMetadata()->hasField('deleted')) {
            $criteria += ['deleted' => 0];
        }

        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria, array $orderBy = null, bool $deleted = false): ?object
    {
        if (false === $deleted && $this->getClassMetadata()->hasField('deleted')) {
            $criteria += ['deleted' => 0];
        }

        return parent::findOneBy($criteria, $orderBy);
    }
}
