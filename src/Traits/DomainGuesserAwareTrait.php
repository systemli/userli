<?php

declare(strict_types=1);

namespace App\Traits;

use App\Guesser\DomainGuesser;

trait DomainGuesserAwareTrait
{
    private DomainGuesser $domainGuesser;

    public function getDomainGuesser(): DomainGuesser
    {
        return $this->domainGuesser;
    }

    public function setDomainGuesser(DomainGuesser $domainGuesser): void
    {
        $this->domainGuesser = $domainGuesser;
    }
}
