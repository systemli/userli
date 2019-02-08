<?php

namespace App\Traits;

/**
 * @author louis <louis@systemli.org>
 */
trait QuotaTrait
{
    /**
     * @var int|null
     */
    private $quota;

    /**
     * @return int|null
     */
    public function getQuota()
    {
        return $this->quota;
    }

    /**
     * @param int|null $quota
     */
    public function setQuota($quota)
    {
        $this->quota = $quota;
    }
}
