<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\Markup;

class SafeHtmlExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('safe_html', $this->safeHtml(...), ['is_safe' => ['html']]),
        ];
    }

    public function safeHtml(string $content): Markup
    {
        // Server-side basic HTML sanitization for security
        // The client-side DOMPurify will provide additional sanitization
        $allowedTags = '<b><i><em><strong><u><br><p><span><div><a>';
        $cleaned = strip_tags($content, $allowedTags);

        // Remove any javascript: or data: URLs
        $cleaned = preg_replace('/href\s*=\s*["\']?\s*(?:javascript|data):/i', 'href="#"', $cleaned);

        // Add a data attribute to indicate this content should be processed by DOMPurify
        $wrapped = sprintf('<div data-safe-html>%s</div>', $cleaned);

        return new Markup($wrapped, 'UTF-8');
    }
}
