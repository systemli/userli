<?php

namespace AppBundle\Traits;

use AppBundle\Guesser\DomainGuesser;

/**
 * @author louis <louis@systemli.org>
 */
trait DomainGuesserAwareTrait
{
    /**
     * @var DomainGuesser
     */
    private $domainGuesser;

    /**
     * @param DomainGuesser $domainGuesser
     */
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
