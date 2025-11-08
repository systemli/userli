<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Domain;
use App\Traits\DomainAwareTrait;
use Symfony\Contracts\EventDispatcher\Event;

class DomainCreatedEvent extends Event
{
    use DomainAwareTrait;

    public const NAME = 'domain.created';

    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
    }
}
