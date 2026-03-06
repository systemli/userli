<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Alias;
use Symfony\Contracts\EventDispatcher\Event;

final class AliasDeletedEvent extends Event
{
    public const string CUSTOM = 'alias.custom_deleted';

    public function __construct(private readonly Alias $alias)
    {
    }

    public function getAlias(): Alias
    {
        return $this->alias;
    }
}
