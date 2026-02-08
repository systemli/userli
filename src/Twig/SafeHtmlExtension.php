<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Attribute\AsTwigFilter;
use Twig\Markup;

final class SafeHtmlExtension
{
    #[AsTwigFilter(name: 'safe_html', isSafe: ['html'])]
    public function safeHtml(string $content): Markup
    {
        // Server-side basic HTML sanitization for security
        // The client-side DOMPurify will provide additional sanitization
        $allowedTags = '<b><i><em><strong><u><br><p><span><div><a>';
        $cleaned = strip_tags($content, $allowedTags);

        // Remove any javascript: or data: URLs
        $cleaned = preg_replace('/href\s*=\s*["\']?\s*(?:javascript|data):/i', 'href="#"', $cleaned);

        // Add a data attribute to indicate this content should be processed by DOMPurify
        // Use span with inherit class to properly inherit text styling (including dark mode colors)
        $wrapped = sprintf('<span data-safe-html class="text-inherit">%s</span>', $cleaned);

        return new Markup($wrapped, 'UTF-8');
    }
}
