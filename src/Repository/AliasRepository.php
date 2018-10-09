<?php

namespace App\Repository;

use App\Entity\Alias;

/**
 * Class AliasRepository.
 */
class AliasRepository extends AbstractRepository
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
