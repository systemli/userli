<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\SettingsService;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SettingsExtension extends AbstractExtension
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('setting', $this->settings->get(...)),
        ];
    }
}
