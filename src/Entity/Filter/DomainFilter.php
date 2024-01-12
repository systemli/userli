<?php

namespace App\Entity\Filter;

use InvalidArgumentException;
use App\Entity\Domain;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class DomainFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if (null === $domainId = $this->getDomainId()) {
            return '';
        }

        // if domain aware
        if (array_key_exists('domain', $targetEntity->getAssociationMappings())) {
            return sprintf('%s.domain_id = %s', $targetTableAlias, $domainId);
        }

        if (Domain::class === $targetEntity->getName()) {
            return sprintf('%s.id = %s', $targetTableAlias, $domainId);
        }

        return '';
    }

    public function getDomainId(): ?string
    {
        try {
            return $this->getParameter('domainId');
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
