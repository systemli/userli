<?php

declare(strict_types=1);

namespace App\Creator;

use App\Entity\ReservedName;
use App\Exception\ValidationException;
use App\Factory\ReservedNameFactory;

/**
 * Class ReservedNameCreator.
 */
class ReservedNameCreator extends AbstractCreator
{
    /**
     * @throws ValidationException
     */
    public function create(string $name): ReservedName
    {
        $reservedName = ReservedNameFactory::create($name);

        $this->validate($reservedName);
        $this->save($reservedName);

        return $reservedName;
    }
}
