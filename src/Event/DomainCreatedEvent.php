<?php

namespace App\Event;

use App\Entity\Domain;
use App\Traits\DomainAwareTrait;
use Symfony\Component\EventDispatcher\Event;

class DomainCreatedEvent extends Event
{
    use DomainAwareTrait;

    const NAME = 'domain.created';

    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
    }
}
