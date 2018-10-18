<?php

namespace App\Creator;

use App\Entity\ReservedName;
use App\Factory\ReservedNameFactory;

/**
 * Class ReservedNameCreator.
 */
class ReservedNameCreator extends AbstractCreator
{
    /**
     * @param string $name
     * @return ReservedName
     * @throws \App\Exception\ValidationException
     */
    public function create(string $name): ReservedName
    {
        $reservedName = ReservedNameFactory::create($name);

        $this->validate($reservedName);
        $this->save($reservedName);

        return $reservedName;
    }
}
