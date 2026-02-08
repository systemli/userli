<?php

declare(strict_types=1);

namespace App\Tests\Creator;

use App\Creator\ReservedNameCreator;
use App\Entity\ReservedName;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ReservedNameCreatorTest extends TestCase
{
    public function testCreate(): void
    {
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('persist')->willReturnCallback(
            static function (ReservedName $reservedName): void {
                $reservedName->setId(1);
            }
        );

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);

        $creator = new ReservedNameCreator($manager, $validator, $eventDispatcher);

        $reservedName = $creator->create('test');

        self::assertEquals('test', $reservedName->getName());
    }
}
