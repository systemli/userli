<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Domain;
use Symfony\Contracts\EventDispatcher\Event;

final class DomainEvent extends Event
{
    public const string CREATED = 'domain.created';

    public const string DELETED = 'domain.deleted';

    public function __construct(private readonly Domain $domain)
    {
    }

    public function getDomain(): Domain
    {
        return $this->domain;
    }
}
