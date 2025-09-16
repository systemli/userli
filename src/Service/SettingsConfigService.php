<?php

declare(strict_types=1);

namespace App\Service;

use App\DependencyInjection\Configuration\SettingsConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

class SettingsConfigService
{
    private array $definitions = [];

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    )
    {
        $this->loadDefinitions();
    }

    private function loadDefinitions(): void
    {
        $configFile = $this->projectDir . '/config/definitions/settings.yaml';

        if (!file_exists($configFile)) {
            return;
        }

        $config = Yaml::parseFile($configFile);
        $processor = new Processor();
        $configuration = new SettingsConfiguration();

        // Pass the 'settings' part of the config to the processor
        $settingsConfig = $config['settings'] ?? [];
        $processedConfig = $processor->processConfiguration($configuration, [$settingsConfig]);
        $this->definitions = $processedConfig['definitions'] ?? [];
    }

    public function getDefinitions(): array
    {
        return $this->definitions;
    }
}
