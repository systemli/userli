<?php

namespace App\Traits;

use App\Guesser\DomainGuesser;

trait DomainGuesserAwareTrait
{
    /**
     * @var DomainGuesser
     */
    private $domainGuesser;

    public function setDomainGuesser(DomainGuesser $domainGuesser)
    {
        $this->domainGuesser = $domainGuesser;
    }

    /**
     * @return DomainGuesser
     */
    public function getDomainGuesser()
    {
        return $this->domainGuesser;
    }
}
