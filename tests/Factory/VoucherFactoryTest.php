<?php

namespace App\Tests\Factory;

use App\Entity\User;
use App\Factory\VoucherFactory;
use PHPUnit\Framework\TestCase;

class VoucherFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $user = new User();

        $voucher = VoucherFactory::create($user);

        self::assertNotNull($voucher->getCreationTime());
        self::assertNotNull($voucher->getUser());
        self::assertNotNull($voucher->getCode());
    }
}
