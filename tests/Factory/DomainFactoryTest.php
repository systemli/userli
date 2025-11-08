<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Factory\DomainFactory;
use PHPUnit\Framework\TestCase;

class DomainFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $name = 'example.com';
        $domain = DomainFactory::create($name);

        $this->assertSame($name, $domain->getName());
    }
}
