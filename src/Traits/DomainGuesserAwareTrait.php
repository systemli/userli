<?php

namespace App\Traits;

use App\Guesser\DomainGuesser;

trait DomainGuesserAwareTrait
{
    /**
     * @var DomainGuesser
     */
    private $domainGuesser;

    public function getDomainGuesser(): DomainGuesser
    {
        return $this->domainGuesser;
    }

    public function setDomainGuesser(DomainGuesser $domainGuesser): void
    {
        $this->domainGuesser = $domainGuesser;
    }
}
