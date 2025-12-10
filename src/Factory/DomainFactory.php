<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Domain;

final class DomainFactory
{
    public static function create(string $name): Domain
    {
        $domain = new Domain();
        $domain->setName($name);

        return $domain;
    }
}
