<?php

declare(strict_types=1);

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\TextToEmailTransformer;
use PHPUnit\Framework\TestCase;

class TextToEmailTransformerTest extends TestCase
{
    private const DOMAIN = 'example.org';

    /**
     * @dataProvider transformProvider
     */
    public function testTransform($input, $expected): void
    {
        $this->assertEquals($expected, $this->getTransformer()->transform($input));
    }

    public function transformProvider(): array
    {
        return [
            ['', ''],
            [null, ''],
            ['louis@example.org', 'louis'],
        ];
    }

    /**
     * @dataProvider reverseTransformProvider
     */
    public function testReverseTransform($input, $expected): void
    {
        $this->assertEquals($expected, $this->getTransformer()->reverseTransform($input));
    }

    public function reverseTransformProvider(): array
    {
        return [
            ['', ''],
            [null, ''],
            ['louis', 'louis@example.org'],
        ];
    }

    private function getTransformer(): TextToEmailTransformer
    {
        return new TextToEmailTransformer(self::DOMAIN);
    }
}
