<?php

declare(strict_types=1);

namespace App\Service;

use App\DependencyInjection\Configuration\SettingsConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

final readonly class SettingsConfigService
{
    private array $settings;

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')] string $projectDir,
    ) {
        $configFile = $projectDir.'/config/settings.yaml';

        if (!file_exists($configFile)) {
            $this->settings = [];

            return;
        }

        $config = Yaml::parseFile($configFile);
        $processor = new Processor();
        $configuration = new SettingsConfiguration();

        $settingsConfig = $config['settings'] ?? [];
        $this->settings = $processor->processConfiguration($configuration, [$settingsConfig]);
    }

    public function getSettings(): array
    {
        return $this->settings;
    }
}
