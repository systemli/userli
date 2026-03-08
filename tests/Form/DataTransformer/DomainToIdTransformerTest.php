<?php

declare(strict_types=1);

namespace App\Tests\Form\DataTransformer;

use App\Entity\Domain;
use App\Form\DataTransformer\DomainToIdTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DomainToIdTransformerTest extends TestCase
{
    public function testTransformNull(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $transformer = new DomainToIdTransformer($em);

        self::assertSame('', $transformer->transform(null));
    }

    public function testTransformDomain(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $transformer = new DomainToIdTransformer($em);

        $domain = new Domain();
        $domain->setId(7);

        self::assertSame('7', $transformer->transform($domain));
    }

    public function testTransformInvalidType(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $transformer = new DomainToIdTransformer($em);

        $this->expectException(TransformationFailedException::class);
        $transformer->transform('not-a-domain');
    }

    public function testReverseTransformEmpty(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $transformer = new DomainToIdTransformer($em);

        self::assertNull($transformer->reverseTransform(''));
        self::assertNull($transformer->reverseTransform(null));
    }

    public function testReverseTransformExistingDomain(): void
    {
        $domain = new Domain();
        $domain->setId(7);

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturn($domain);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $transformer = new DomainToIdTransformer($em);

        self::assertSame($domain, $transformer->reverseTransform('7'));
    }

    public function testReverseTransformNonExistingDomain(): void
    {
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('find')->willReturn(null);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $transformer = new DomainToIdTransformer($em);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Domain with ID "999" does not exist.');
        $transformer->reverseTransform('999');
    }
}
