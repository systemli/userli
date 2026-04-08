<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Twig\SafeHtmlExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Twig\Markup;

class SafeHtmlExtensionTest extends TestCase
{
    private static SafeHtmlExtension $extension;

    public static function setUpBeforeClass(): void
    {
        $config = new HtmlSanitizerConfig()->allowSafeElements();
        $sanitizer = new HtmlSanitizer($config);
        self::$extension = new SafeHtmlExtension($sanitizer);
    }

    public function testSafeHtmlReturnsMarkupInstance(): void
    {
        $result = self::$extension->safeHtml('Hello');

        self::assertInstanceOf(Markup::class, $result);
    }

    public function testSafeHtmlReturnsSanitizedContent(): void
    {
        $result = (string) self::$extension->safeHtml('Hello');

        self::assertSame('Hello', $result);
    }

    public function testSafeHtmlAllowsSafeTags(): void
    {
        $input = '<b>bold</b> <i>italic</i> <em>em</em> <strong>strong</strong> <u>underline</u>';
        $result = (string) self::$extension->safeHtml($input);

        self::assertStringContainsString('<b>bold</b>', $result);
        self::assertStringContainsString('<i>italic</i>', $result);
        self::assertStringContainsString('<em>em</em>', $result);
        self::assertStringContainsString('<strong>strong</strong>', $result);
        self::assertStringContainsString('<u>underline</u>', $result);
    }

    public function testSafeHtmlAllowsHeadingsAndLists(): void
    {
        $input = '<h3>Title</h3><ul><li>Item 1</li><li>Item 2</li></ul><ol><li>First</li></ol>';
        $result = (string) self::$extension->safeHtml($input);

        self::assertStringContainsString('<h3>Title</h3>', $result);
        self::assertStringContainsString('<ul><li>Item 1</li><li>Item 2</li></ul>', $result);
        self::assertStringContainsString('<ol><li>First</li></ol>', $result);
    }

    public function testSafeHtmlAllowsBlockquoteAndHr(): void
    {
        $input = '<blockquote>Quote</blockquote><hr>';
        $result = (string) self::$extension->safeHtml($input);

        self::assertStringContainsString('<blockquote>Quote</blockquote>', $result);
        self::assertStringContainsString('<hr />', $result);
    }

    public function testSafeHtmlStripsDisallowedTags(): void
    {
        $input = '<script>alert("xss")</script><img src="x" onerror="alert(1)">';
        $result = (string) self::$extension->safeHtml($input);

        self::assertStringNotContainsString('<script>', $result);
        self::assertStringNotContainsString('onerror', $result);
    }

    public function testSafeHtmlStripsEventHandlers(): void
    {
        $input = '<a href="https://example.org" onclick="alert(1)">click</a>';
        $result = (string) self::$extension->safeHtml($input);

        self::assertStringNotContainsString('onclick', $result);
        self::assertStringContainsString('href="https://example.org"', $result);
    }

    public function testSafeHtmlStripsEventHandlersOnAllowedTags(): void
    {
        $input = '<div onmouseover="alert(1)">hover</div><span onfocus="alert(1)">focus</span>';
        $result = (string) self::$extension->safeHtml($input);

        self::assertStringNotContainsString('onmouseover', $result);
        self::assertStringNotContainsString('onfocus', $result);
        self::assertStringContainsString('hover', $result);
        self::assertStringContainsString('focus', $result);
    }

    public function testSafeHtmlStripsStyleAttribute(): void
    {
        $input = '<div style="background-image:url(javascript:alert(1))">content</div>';
        $result = (string) self::$extension->safeHtml($input);

        self::assertStringNotContainsString('style', $result);
        self::assertStringContainsString('content', $result);
    }

    #[DataProvider('javascriptUrlProvider')]
    public function testSafeHtmlRemovesJavascriptUrls(string $input, string $notExpected): void
    {
        $result = (string) self::$extension->safeHtml($input);

        self::assertStringNotContainsString($notExpected, $result);
        self::assertStringNotContainsString('href=', $result);
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
        $result = (string) self::$extension->safeHtml($input);

        self::assertStringContainsString('href="https://example.org"', $result);
        self::assertStringContainsString('>link</a>', $result);
    }

    public function testSafeHtmlHandlesEmptyString(): void
    {
        $result = (string) self::$extension->safeHtml('');

        self::assertSame('', $result);
    }

    public function testSafeHtmlHandlesPlainText(): void
    {
        $result = (string) self::$extension->safeHtml('Just plain text');

        self::assertStringContainsString('Just plain text', $result);
    }
}
