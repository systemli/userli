<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Alias;
use Symfony\Contracts\EventDispatcher\Event;

final class AliasEvent extends Event
{
    public const string CUSTOM_CREATED = 'alias.custom_created';

    public const string RANDOM_CREATED = 'alias.random_created';

    public const string CUSTOM_DELETED = 'alias.custom_deleted';

    public function __construct(private readonly Alias $alias)
    {
    }

    public function getAlias(): Alias
    {
        return $this->alias;
    }
}
