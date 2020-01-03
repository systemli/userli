<?php

namespace App\Factory;

use App\Entity\Domain;

class DomainFactory
{
    public static function create(string $name): Domain
    {
        $domain = new Domain();
        $domain->setName($name);
        $time = new \DateTime('now');
        $domain->setCreationTime($time);
        $domain->setUpdatedTime($time);

        return $domain;
    }
}
