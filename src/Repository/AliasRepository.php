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
     * @param      $email
     * @param bool $deleted
     *
     * @return object|Alias|null
     */
    public function findOneBySource($email, ?bool $deleted = false)
    {
        return $this->findOneBy(['source' => $email], null, $deleted);
    }

    /**
     * @param $email
     *
     * @return object|Alias|null
     */
    public function findByDestination($email)
    {
        return $this->findOneBy(['destination' => $email]);
    }

    /**
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
