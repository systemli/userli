<?php

namespace App\Entity\Filter;

use App\Entity\Alias;
use App\Entity\Domain;
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
        $domainId = null;

        try {
            $domainId = $this->getParameter('domainId');
        } catch (\InvalidArgumentException $e) {
            return '';
        }

        $entityName = $targetEntity->getName();

        // if domain aware
        if (array_key_exists('domain', $targetEntity->getAssociationMappings())) {
            return sprintf('%s.domain_id = %s', $targetTableAlias, $domainId);
        }

        if ($entityName === Domain::class) {
            return sprintf('%s.id = %s', $targetTableAlias, $domainId);
        }

        return '';
    }
}
