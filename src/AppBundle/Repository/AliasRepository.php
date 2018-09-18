<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Alias;
use Doctrine\ORM\EntityRepository;

/**
 * Class AliasRepository.
 */
class AliasRepository extends EntityRepository
{
    /**
     * @param $email
     *
     * @return null|object|Alias
     */
    public function findBySource($email)
    {
        return $this->findOneBy(array('source' => $email));
    }

    /**
     * @param $email
     *
     * @return null|object|Alias
     */
    public function findByDestination($email)
    {
        return $this->findOneBy(array('destination' => $email));
    }
}
