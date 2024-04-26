<?php

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
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('persist')->willReturnCallback(function ($entity) {
            $entity->setId(1);
        });
        $manager->method('flush')->willReturn(true);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        return new DomainCreator($manager, $validator, $eventDispatcher);
    }

    public function testCreate()
    {
        $creator = $this->createCreator();
        $entity = $creator->create('test');
        $this->assertEquals(1, $entity->getId());
        $this->assertEquals('test', $entity->getName());
    }
}
