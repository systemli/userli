<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;

/**
 * @author louis <louis@systemli.org>
 */
abstract class Admin extends AbstractAdmin
{
    /**
     * @return bool
     */
    protected function isNewObject()
    {
        return !$this->getRequest()->get($this->getIdParameter());
    }
}
