<?php

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\OptionalDomainEmailTransformer;
use PHPUnit\Framework\TestCase;

class OptionalDomainEmailTransformerTest extends TestCase
{
    const DOMAIN = 'systemli.org';

    /**
     * @dataProvider transformProvider
     */
    public function testTransform($input, $expected)
    {
        $this->assertEquals($expected, $this->getTransformer()->transform($input));
    }

    public function transformProvider()
    {
        return [
            ['', ''],
            [null, ''],
            ['louis@systemli.org', 'louis'],
            ['louis', 'louis'],
        ];
    }

    /**
     * @dataProvider reverseTransformProvider
     */
    public function testReverseTransform($input, $expected)
    {
        $this->assertEquals($expected, $this->getTransformer()->reverseTransform($input));
    }

    public function reverseTransformProvider()
    {
        return [
            ['', ''],
            [null, ''],
            ['louis', 'louis@systemli.org'],
            ['louis@example.org', 'louis@example.org'],
        ];
    }

    /**
     * @return TextToEmailTransformer
     */
    private function getTransformer()
    {
        return new OptionalDomainEmailTransformer(self::DOMAIN);
    }
}
