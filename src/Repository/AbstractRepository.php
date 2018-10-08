<?php

namespace App\Repository;

use App\Entity\SoftDeletableInterface;
use Doctrine\ORM\EntityRepository;

/**
 * Class AbstractRepository
 */
class AbstractRepository extends EntityRepository
{
    /**
     * @param mixed $id
     * @param null $lockMode
     * @param null $lockVersion
     * @param bool $deleted
     * @return null|object
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function find($id, $lockMode = null, $lockVersion = null, $deleted = false)
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
     * @param bool $deleted
     * @return array
     */
    public function findAll($deleted = false)
    {
        return $this->findBy([], null, null, null, $deleted);
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param null $limit
     * @param null $offset
     * @param bool $deleted
     * @return array
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null, $deleted = false)
    {
        if (false === $deleted && $this->getClassMetadata()->hasField('deleted')) {
            $criteria += ['deleted' => 0];
        }

        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param bool $deleted
     * @return null|object
     */
    public function findOneBy(array $criteria, array $orderBy = null, $deleted = false)
    {
        if (false === $deleted && $this->getClassMetadata()->hasField('deleted')) {
            $criteria += ['deleted' => 0];
        }

        return parent::findOneBy($criteria, $orderBy);
    }
}
