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
    }

    public function testNoteIsLimitedToFortyCharacters(): void
    {
        $alias = new Alias();

        $long = str_repeat('x', 60);

        $alias->setNote($long);

        $note = $alias->getNote();
        $this->assertNotNull($note);
        $this->assertLessThanOrEqual(40, mb_strlen($note));
        $this->assertSame(mb_substr($long, 0, 40), $note);
    }
}
