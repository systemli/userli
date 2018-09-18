<?php

namespace AppBundle\Repository;

use AppBundle\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

/**
 * Class UserRepository.
 */
class UserRepository extends EntityRepository
{
    /**
     * @param $email
     *
     * @return null|object|User
     */
    public function findByEmail($email)
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * @param \DateTime $dateTime
     *
     * @return \Doctrine\Common\Collections\Collection|User[]
     */
    public function findUsersSince(\DateTime $dateTime)
    {
        return $this->matching(Criteria::create()->where(Criteria::expr()->gte('creationTime', $dateTime)));
    }

    /**
     * @return array|User[]
     */
    public function findDeletedUsers()
    {
        return $this->findBy(['deleted' => true]);
    }
}
