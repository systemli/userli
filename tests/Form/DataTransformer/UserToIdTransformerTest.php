<?php

declare(strict_types=1);

namespace App\Tests\Form\DataTransformer;

use App\Entity\User;
use App\Form\DataTransformer\UserToIdTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Form\Exception\TransformationFailedException;

class UserToIdTransformerTest extends TestCase
{
    public function testTransformNull(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $transformer = new UserToIdTransformer($em);

        self::assertSame('', $transformer->transform(null));
    }

    public function testTransformUser(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $transformer = new UserToIdTransformer($em);

        $user = new User('test@example.org');
        $reflection = new ReflectionProperty(User::class, 'id');
        $reflection->setValue($user, 42);

        self::assertSame('42', $transformer->transform($user));
    }

    public function testTransformInvalidType(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $transformer = new UserToIdTransformer($em);

        $this->expectException(TransformationFailedException::class);
        $transformer->transform('not-a-user');
    }

    public function testReverseTransformEmpty(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $transformer = new UserToIdTransformer($em);

        self::assertNull($transformer->reverseTransform(''));
        self::assertNull($transformer->reverseTransform(null));
    }

    public function testReverseTransformExistingUser(): void
    {
        $user = new User('test@example.org');

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturn($user);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $transformer = new UserToIdTransformer($em);

        self::assertSame($user, $transformer->reverseTransform('42'));
    }

    public function testReverseTransformNonExistingUser(): void
    {
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturn(null);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $transformer = new UserToIdTransformer($em);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('User with ID "999" does not exist.');
        $transformer->reverseTransform('999');
    }
}
