<?php

declare(strict_types=1);

namespace App\Tests\Creator;

use App\Creator\DomainCreator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DomainCreatorTest extends TestCase
{
    private function createCreator(): DomainCreator
    {
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('persist')->willReturnCallback(static function ($entity): void {
            $entity->setId(1);
        });

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);

        return new DomainCreator($manager, $validator, $eventDispatcher);
    }

    public function testCreate(): void
    {
        $creator = $this->createCreator();
        $entity = $creator->create('test');
        self::assertEquals(1, $entity->getId());
        self::assertEquals('test', $entity->getName());
    }
}
