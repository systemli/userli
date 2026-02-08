<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Domain;
use Symfony\Contracts\EventDispatcher\Event;

final class DomainCreatedEvent extends Event
{
    public const string NAME = 'domain.created';

    public function __construct(private readonly Domain $domain)
    {
    }

    public function getDomain(): Domain
    {
        return $this->domain;
    }
}
