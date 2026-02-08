<?php

declare(strict_types=1);

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\TextToEmailTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TextToEmailTransformerTest extends TestCase
{
    private const DOMAIN = 'example.org';

    #[DataProvider('transformProvider')]
    public function testTransform($input, $expected): void
    {
        $this->assertEquals($expected, $this->getTransformer()->transform($input));
    }

    public static function transformProvider(): array
    {
        return [
            ['', ''],
            [null, ''],
            ['louis@example.org', 'louis'],
        ];
    }

    #[DataProvider('reverseTransformProvider')]
    public function testReverseTransform($input, $expected): void
    {
        $this->assertEquals($expected, $this->getTransformer()->reverseTransform($input));
    }

    public static function reverseTransformProvider(): array
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
