<?php

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\TextToEmailTransformer;
use PHPUnit\Framework\TestCase;

class TextToEmailTransformerTest extends TestCase
{
    const DOMAIN = 'example.org';

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
            ['louis@example.org', 'louis'],
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
            ['louis', 'louis@example.org'],
        ];
    }

    /**
     * @return TextToEmailTransformer
     */
    private function getTransformer()
    {
        return new TextToEmailTransformer(self::DOMAIN);
    }
}
