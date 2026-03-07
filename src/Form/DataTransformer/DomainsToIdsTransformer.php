<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Entity\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforms an array of Domain entities to/from a comma-separated string of IDs.
 *
 * @implements DataTransformerInterface<array<Domain>, string>
 */
final readonly class DomainsToIdsTransformer implements DataTransformerInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * Transforms an array of Domain entities to a comma-separated ID string.
     */
    #[Override]
    public function transform(mixed $value): mixed
    {
        if (null === $value || [] === $value) {
            return '';
        }

        if (!is_array($value)) {
            throw new TransformationFailedException('Expected an array of Domain entities.');
        }

        $ids = array_map(static function (mixed $domain): string {
            if (!$domain instanceof Domain) {
                throw new TransformationFailedException('Expected a Domain entity.');
            }

            return (string) $domain->getId();
        }, $value);

        return implode(',', $ids);
    }

    /**
     * Transforms a comma-separated ID string back to an array of Domain entities.
     */
    #[Override]
    public function reverseTransform(mixed $value): mixed
    {
        if (null === $value || '' === $value) {
            return [];
        }

        $ids = array_filter(array_map(trim(...), explode(',', (string) $value)));

        if ([] === $ids) {
            return [];
        }

        $domains = $this->em->getRepository(Domain::class)->findBy(['id' => array_map(intval(...), $ids)]);

        if (\count($domains) !== \count($ids)) {
            throw new TransformationFailedException('One or more domain IDs do not exist.');
        }

        return $domains;
    }
}
