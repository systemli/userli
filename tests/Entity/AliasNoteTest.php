<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Alias;
use PHPUnit\Framework\TestCase;

class AliasNoteTest extends TestCase
{
    public function testNoteProperty(): void
    {
        $alias = new Alias();
        $this->assertNull($alias->getNote());
        $alias->setNote('Test note');
        $this->assertSame('Test note', $alias->getNote());
        $alias->setNote(null);
        $this->assertNull($alias->getNote());
    }
}
