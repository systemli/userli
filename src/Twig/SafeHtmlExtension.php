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
        return new Markup($this->sanitizer->sanitize($content), 'UTF-8');
    }
}
