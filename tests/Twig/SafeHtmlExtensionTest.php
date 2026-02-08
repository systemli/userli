<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Twig\SafeHtmlExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Markup;

class SafeHtmlExtensionTest extends TestCase
{
    private SafeHtmlExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new SafeHtmlExtension();
    }

    public function testSafeHtmlReturnsMarkupInstance(): void
    {
        $result = $this->extension->safeHtml('Hello');

        self::assertInstanceOf(Markup::class, $result);
    }

    public function testSafeHtmlWrapsContentInSpan(): void
    {
        $result = (string) $this->extension->safeHtml('Hello');

        self::assertStringContainsString('<span data-safe-html class="text-inherit">', $result);
        self::assertStringContainsString('Hello', $result);
        self::assertStringEndsWith('</span>', $result);
    }

    public function testSafeHtmlAllowsSafeTags(): void
    {
        $input = '<b>bold</b> <i>italic</i> <em>em</em> <strong>strong</strong> <u>underline</u>';
        $result = (string) $this->extension->safeHtml($input);

        self::assertStringContainsString('<b>bold</b>', $result);
        self::assertStringContainsString('<i>italic</i>', $result);
        self::assertStringContainsString('<em>em</em>', $result);
        self::assertStringContainsString('<strong>strong</strong>', $result);
        self::assertStringContainsString('<u>underline</u>', $result);
    }

    public function testSafeHtmlStripsDisallowedTags(): void
    {
        $input = '<script>alert("xss")</script><img src="x" onerror="alert(1)">';
        $result = (string) $this->extension->safeHtml($input);

        self::assertStringNotContainsString('<script>', $result);
        self::assertStringNotContainsString('<img', $result);
        self::assertStringContainsString('alert("xss")', $result);
    }

    #[DataProvider('javascriptUrlProvider')]
    public function testSafeHtmlRemovesJavascriptUrls(string $input, string $notExpected): void
    {
        $result = (string) $this->extension->safeHtml($input);

        self::assertStringNotContainsString($notExpected, $result);
        self::assertStringContainsString('href="#"', $result);
    }

    public static function javascriptUrlProvider(): array
    {
        return [
            'lowercase javascript:' => [
                '<a href="javascript:alert(1)">click</a>',
                'javascript:',
            ],
            'uppercase JAVASCRIPT:' => [
                '<a href="JAVASCRIPT:alert(1)">click</a>',
                'JAVASCRIPT:',
            ],
            'data: url' => [
                '<a href="data:text/html,<script>alert(1)</script>">click</a>',
                'data:',
            ],
        ];
    }

    public function testSafeHtmlAllowsNormalLinks(): void
    {
        $input = '<a href="https://example.org">link</a>';
        $result = (string) $this->extension->safeHtml($input);

        self::assertStringContainsString('href="https://example.org"', $result);
        self::assertStringContainsString('>link</a>', $result);
    }

    public function testSafeHtmlHandlesEmptyString(): void
    {
        $result = (string) $this->extension->safeHtml('');

        self::assertSame('<span data-safe-html class="text-inherit"></span>', $result);
    }

    public function testSafeHtmlHandlesPlainText(): void
    {
        $result = (string) $this->extension->safeHtml('Just plain text');

        self::assertStringContainsString('Just plain text', $result);
    }
}
