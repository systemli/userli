<?php

namespace App\Repository;

use App\Entity\Alias;
use App\Entity\User;

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
    public function findOneBySource($email)
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

    /**
     * @param User      $user
     * @param null|bool $random
     *
     * @return array|Alias[]
     */
    public function findByUser(User $user, ?bool $random = null)
    {
        if (isset($random)) {
            return $this->findBy(['user' => $user, 'random' => $random]);
        } else {
            return $this->findBy(['user' => $user]);
        }
    }
}
