<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<User|null, string>
 */
final readonly class UserToIdTransformer implements DataTransformerInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * Transforms a User entity to its ID string (for the hidden form field).
     */
    #[Override]
    public function transform(mixed $value): mixed
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof User) {
            throw new TransformationFailedException('Expected a User entity.');
        }

        return (string) $value->getId();
    }

    /**
     * Transforms an ID string back to a User entity.
     */
    #[Override]
    public function reverseTransform(mixed $value): mixed
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $user = $this->em->getRepository(User::class)->find((int) $value);

        if (null === $user) {
            throw new TransformationFailedException(sprintf('User with ID "%s" does not exist.', $value));
        }

        return $user;
    }
}
