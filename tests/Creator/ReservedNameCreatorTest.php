<?php

namespace App\Tests\Creator;

use App\Creator\ReservedNameCreator;
use App\Entity\ReservedName;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ReservedNameCreatorTest extends TestCase
{
    public function testCreate()
    {
        $manager = $this->getMockBuilder(ObjectManager::class)->getMock();
        $manager->expects($this->any())->method('persist')->willReturnCallback(
            function (ReservedName $reservedName) {
                $reservedName->setId(1);
            }
        );
        $manager->expects($this->any())->method('flush')->willReturn(true);

        $validator = $this->getMockBuilder(ValidatorInterface::class)->getMock();
        $validator->expects($this->any())->method('validate')->willReturn(new ConstraintViolationList());

        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $creator = new ReservedNameCreator($manager, $validator, $eventDispatcher);

        $reservedName = $creator->create('test');

        self::assertEquals('test', $reservedName->getName());
    }
}
