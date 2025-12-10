<?php

declare(strict_types=1);

namespace App\Creator;

use App\Entity\Domain;
use App\Event\DomainCreatedEvent;
use App\Exception\ValidationException;
use App\Factory\DomainFactory;

final class DomainCreator extends AbstractCreator
{
    /**
     * @throws ValidationException
     */
    public function create(string $name): Domain
    {
        $domain = DomainFactory::create($name);

        $this->validate($domain, ['Default', 'lowercase', 'unique']);
        $this->save($domain);

        $this->eventDispatcher->dispatch(new DomainCreatedEvent($domain), DomainCreatedEvent::NAME);

        return $domain;
    }
}
