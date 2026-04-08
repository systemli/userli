<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Twig\Attribute\AsTwigFilter;
use Twig\Markup;

final readonly class SafeHtmlExtension
{
    public function __construct(
        #[Target('app.safe_html_sanitizer')]
        private HtmlSanitizerInterface $sanitizer,
    ) {
    }

    #[AsTwigFilter(name: 'safe_html', isSafe: ['html'])]
    public function safeHtml(string $content): Markup
    {
        $cleaned = $this->sanitizer->sanitize($content);

        // Add a data attribute to indicate this content should be processed by DOMPurify
        // Use span with inherit class to properly inherit text styling (including dark mode colors)
        $wrapped = sprintf('<span data-safe-html class="text-inherit">%s</span>', $cleaned);

        return new Markup($wrapped, 'UTF-8');
    }
}
