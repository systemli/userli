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
     *
     * @param bool $deleted
     */
    public function find($id, $lockMode = null, $lockVersion = null, bool $deleted = false)
    {
        $entity = parent::find($id, $lockMode, $lockVersion);
        if ($entity instanceof SoftDeletableInterface) {
            if (false === $deleted && $entity->isDeleted()) {
                return null;
            }
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $deleted
     */
    public function findAll(bool $deleted = false)
    {
        return $this->findBy([], null, null, null, $deleted);
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $deleted
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null, bool $deleted = false)
    {
        if (false === $deleted && $this->getClassMetadata()->hasField('deleted')) {
            $criteria += ['deleted' => 0];
        }

        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $deleted
     */
    public function findOneBy(array $criteria, array $orderBy = null, bool $deleted = false)
    {
        if (false === $deleted && $this->getClassMetadata()->hasField('deleted')) {
            $criteria += ['deleted' => 0];
        }

        return parent::findOneBy($criteria, $orderBy);
    }
}
