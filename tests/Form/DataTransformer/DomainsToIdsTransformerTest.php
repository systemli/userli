<?php

declare(strict_types=1);

namespace App\Tests\Form\DataTransformer;

use App\Entity\Domain;
use App\Form\DataTransformer\DomainsToIdsTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DomainsToIdsTransformerTest extends TestCase
{
    public function testTransformNull(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $transformer = new DomainsToIdsTransformer($em);

        self::assertSame('', $transformer->transform(null));
    }

    public function testTransformEmptyArray(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $transformer = new DomainsToIdsTransformer($em);

        self::assertSame('', $transformer->transform([]));
    }

    public function testTransformDomains(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $transformer = new DomainsToIdsTransformer($em);

        $domain1 = new Domain();
        $domain1->setId(3);

        $domain2 = new Domain();
        $domain2->setId(5);

        self::assertSame('3,5', $transformer->transform([$domain1, $domain2]));
    }

    public function testTransformSingleDomain(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $transformer = new DomainsToIdsTransformer($em);

        $domain = new Domain();
        $domain->setId(10);

        self::assertSame('10', $transformer->transform([$domain]));
    }

    public function testTransformInvalidType(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $transformer = new DomainsToIdsTransformer($em);

        $this->expectException(TransformationFailedException::class);
        $transformer->transform('not-an-array');
    }

    public function testTransformArrayWithInvalidElement(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $transformer = new DomainsToIdsTransformer($em);

        $this->expectException(TransformationFailedException::class);
        $transformer->transform(['not-a-domain']);
    }

    public function testReverseTransformEmpty(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $transformer = new DomainsToIdsTransformer($em);

        self::assertSame([], $transformer->reverseTransform(''));
        self::assertSame([], $transformer->reverseTransform(null));
    }

    public function testReverseTransformExistingDomains(): void
    {
        $domain1 = new Domain();
        $domain1->setId(3);

        $domain2 = new Domain();
        $domain2->setId(5);

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findBy')->willReturn([$domain1, $domain2]);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $transformer = new DomainsToIdsTransformer($em);

        $result = $transformer->reverseTransform('3,5');
        self::assertCount(2, $result);
        self::assertSame($domain1, $result[0]);
        self::assertSame($domain2, $result[1]);
    }

    public function testReverseTransformSingleId(): void
    {
        $domain = new Domain();
        $domain->setId(7);

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findBy')->willReturn([$domain]);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $transformer = new DomainsToIdsTransformer($em);

        $result = $transformer->reverseTransform('7');
        self::assertCount(1, $result);
        self::assertSame($domain, $result[0]);
    }

    public function testReverseTransformNonExistingDomain(): void
    {
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findBy')->willReturn([]);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $transformer = new DomainsToIdsTransformer($em);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('One or more domain IDs do not exist.');
        $transformer->reverseTransform('999');
    }

    public function testReverseTransformPartiallyMissingDomains(): void
    {
        $domain = new Domain();
        $domain->setId(3);

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findBy')->willReturn([$domain]);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $transformer = new DomainsToIdsTransformer($em);

        $this->expectException(TransformationFailedException::class);
        $transformer->reverseTransform('3,999');
    }

    public function testReverseTransformWhitespaceHandling(): void
    {
        $domain1 = new Domain();
        $domain1->setId(3);

        $domain2 = new Domain();
        $domain2->setId(5);

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findBy')->willReturn([$domain1, $domain2]);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $transformer = new DomainsToIdsTransformer($em);

        $result = $transformer->reverseTransform(' 3 , 5 ');
        self::assertCount(2, $result);
    }
}
