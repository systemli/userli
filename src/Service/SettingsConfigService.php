<?php

declare(strict_types=1);

namespace App\Service;

use App\DependencyInjection\Configuration\SettingsConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

class SettingsConfigService
{
    private array $settings = [];

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')] private readonly string $projectDir,
    ) {
        $this->loadDefaults();
    }

    private function loadDefaults(): void
    {
        $configFile = $this->projectDir.'/config/settings.yaml';

        if (!file_exists($configFile)) {
            return;
        }

        $config = Yaml::parseFile($configFile);
        $processor = new Processor();
        $configuration = new SettingsConfiguration();

        // Pass the 'settings' part of the config to the processor
        $settingsConfig = $config['settings'] ?? [];
        $this->settings = $processor->processConfiguration($configuration, [$settingsConfig]);
    }

    public function getSettings(): array
    {
        return $this->settings;
    }
}
