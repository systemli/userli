<?php

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\OptionalDomainEmailTransformer;
use PHPUnit\Framework\TestCase;

class OptionalDomainEmailTransformerTest extends TestCase
{
    public const DOMAIN = 'example.org';

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
            ['louis', 'louis'],
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
            ['louis@example.org', 'louis@example.org'],
        ];
    }

    private function getTransformer(): OptionalDomainEmailTransformer
    {
        return new OptionalDomainEmailTransformer(self::DOMAIN);
    }
}
