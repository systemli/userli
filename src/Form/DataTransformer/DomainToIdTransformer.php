<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Entity\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<Domain|null, string>
 */
final readonly class DomainToIdTransformer implements DataTransformerInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * Transforms a Domain entity to its ID string (for the hidden form field).
     */
    #[Override]
    public function transform(mixed $value): mixed
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof Domain) {
            throw new TransformationFailedException('Expected a Domain entity.');
        }

        return (string) $value->getId();
    }

    /**
     * Transforms an ID string back to a Domain entity.
     */
    #[Override]
    public function reverseTransform(mixed $value): mixed
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $domain = $this->em->getRepository(Domain::class)->find((int) $value);

        if (null === $domain) {
            throw new TransformationFailedException(sprintf('Domain with ID "%s" does not exist.', $value));
        }

        return $domain;
    }
}
