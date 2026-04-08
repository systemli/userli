<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Twig\Attribute\AsTwigFilter;
use Twig\Markup;

final readonly class SafeHtmlExtension
{
    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = new HtmlSanitizerConfig()
            ->allowElement('b')
            ->allowElement('i')
            ->allowElement('em')
            ->allowElement('strong')
            ->allowElement('u')
            ->allowElement('br')
            ->allowElement('p')
            ->allowElement('span')
            ->allowElement('div')
            ->allowElement('a', ['href', 'target'])
            ->allowElement('h1')
            ->allowElement('h2')
            ->allowElement('h3')
            ->allowElement('h4')
            ->allowElement('h5')
            ->allowElement('h6')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('dl')
            ->allowElement('dt')
            ->allowElement('dd')
            ->allowElement('blockquote')
            ->allowElement('hr');

        $this->sanitizer = new HtmlSanitizer($config);
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
