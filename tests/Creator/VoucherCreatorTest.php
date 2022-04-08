<?php

namespace App\Tests\Creator;

use App\Creator\VoucherCreator;
use App\Entity\User;
use App\Entity\Voucher;
use App\Exception\ValidationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VoucherCreatorTest extends TestCase
{
    public function testCreate(): void
    {
        $manager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $manager->method('persist')->willReturnCallback(
            function (Voucher $voucher) {
                $voucher->setId(1);
            }
        );
        $manager->method('flush')->willReturn(true);

        $validator = $this->getMockBuilder(ValidatorInterface::class)->getMock();
        $validator->method('validate')->willReturn(new ConstraintViolationList());

        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $creator = new VoucherCreator($manager, $validator, $eventDispatcher);

        $user = new User();

        $voucher = $creator->create($user);

        self::assertEquals(1, $voucher->getId());
    }

    public function testCreateWithException(): void
    {
        $manager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();

        $violation = new ConstraintViolation('message', 'messageTemplate', [], null, null, 'someValue');

        $validator = $this->getMockBuilder(ValidatorInterface::class)->getMock();
        $validator->method('validate')->willReturn(new ConstraintViolationList([$violation]));

        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $creator = new VoucherCreator($manager, $validator, $eventDispatcher);

        $user = new User();

        $this->expectException(ValidationException::class);

        $creator->create($user);
    }
}
