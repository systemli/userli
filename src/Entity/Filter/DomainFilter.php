<?php

namespace App\Entity\Filter;

use App\Entity\User;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * @author tim <tim@systemli.org>
 */
class DomainFilter extends SQLFilter
{

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if ($targetEntity->getName() !== User::class) {
            return '';
        }

        return sprintf('%s.domain_id = %s',$targetTableAlias, $this->getParameter('domainId'));
    }
}
