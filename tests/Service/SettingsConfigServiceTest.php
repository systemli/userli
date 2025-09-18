<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\SettingsConfigService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class SettingsConfigServiceTest extends TestCase
{
    private string $tempDir;
    private string $configDir;
    private string $configFile;

    protected function setUp(): void
    {
        // Create temporary directory structure
        $this->tempDir = sys_get_temp_dir() . '/settings_config_test_' . uniqid();
        $this->configDir = $this->tempDir . '/config';
        $this->configFile = $this->configDir . '/settings.yaml';

        mkdir($this->configDir, 0777, true);
    }

    protected function tearDown(): void
    {
        // Clean up temporary files
        if (file_exists($this->configFile)) {
            unlink($this->configFile);
        }
        if (is_dir($this->configDir)) {
            rmdir($this->configDir);
        }
        if (is_dir(dirname($this->configDir))) {
            rmdir(dirname($this->configDir));
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testLoadDefinitionsWithValidConfig(): void
    {
        $configData = [
            'settings' => [
                'app_name' => [
                    'type' => 'string',
                    'default' => 'Userli',
                    'validation' => [
                        'min_length' => 1,
                        'max_length' => 255,
                    ]
                ],
                'registration_enabled' => [
                    'type' => 'boolean',
                    'default' => true,
                ],
                'max_users' => [
                    'type' => 'integer',
                    'default' => 1000,
                    'validation' => [
                        'min' => 1,
                        'max' => 10000,
                    ]
                ],
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);
        $settings = $service->getSettings();

        self::assertIsArray($settings);
        self::assertCount(3, $settings);

        // Test app_name definition
        self::assertArrayHasKey('app_name', $settings);
        self::assertEquals('string', $settings['app_name']['type']);
        self::assertEquals('Userli', $settings['app_name']['default']);
        self::assertEquals(1, $settings['app_name']['validation']['min_length']);
        self::assertEquals(255, $settings['app_name']['validation']['max_length']);

        // Test registration_enabled definition
        self::assertArrayHasKey('registration_enabled', $settings);
        self::assertEquals('boolean', $settings['registration_enabled']['type']);
        self::assertTrue($settings['registration_enabled']['default']);

        // Test max_users definition
        self::assertArrayHasKey('max_users', $settings);
        self::assertEquals('integer', $settings['max_users']['type']);
        self::assertEquals(1000, $settings['max_users']['default']);
        self::assertEquals(1, $settings['max_users']['validation']['min']);
        self::assertEquals(10000, $settings['max_users']['validation']['max']);
    }

    public function testLoadDefinitionsWithMinimalConfig(): void
    {
        $configData = [
            'settings' => [
                'simple_setting' => [
                    'type' => 'string',
                    'default' => 'value',
                ]
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);
        $settings = $service->getSettings();

        self::assertCount(1, $settings);
        self::assertArrayHasKey('simple_setting', $settings);
        self::assertEquals('string', $settings['simple_setting']['type']);
        self::assertEquals('value', $settings['simple_setting']['default']);
        self::assertArrayHasKey('validation', $settings['simple_setting']);
    }

    public function testLoadDefinitionsWithAllSupportedTypes(): void
    {
        $configData = [
            'settings' => [
                'string_setting' => [
                    'type' => 'string',
                    'default' => 'text',
                ],
                'email_setting' => [
                    'type' => 'email',
                    'default' => 'test@example.com',
                ],
                'url_setting' => [
                    'type' => 'url',
                    'default' => 'https://example.com',
                ],
                'password_setting' => [
                    'type' => 'password',
                    'default' => 'secret',
                ],
                'textarea_setting' => [
                    'type' => 'textarea',
                    'default' => 'long text',
                ],
                'integer_setting' => [
                    'type' => 'integer',
                    'default' => 42,
                ],
                'float_setting' => [
                    'type' => 'float',
                    'default' => 3.14,
                ],
                'boolean_setting' => [
                    'type' => 'boolean',
                    'default' => true,
                ],
                'array_setting' => [
                    'type' => 'array',
                    'default' => '["a","b","c"]', // Array as JSON string for scalar compatibility
                ],
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);
        $settings = $service->getSettings();

        self::assertCount(9, $settings);

        // Verify all types are preserved
        self::assertEquals('string', $settings['string_setting']['type']);
        self::assertEquals('email', $settings['email_setting']['type']);
        self::assertEquals('url', $settings['url_setting']['type']);
        self::assertEquals('password', $settings['password_setting']['type']);
        self::assertEquals('textarea', $settings['textarea_setting']['type']);
        self::assertEquals('integer', $settings['integer_setting']['type']);
        self::assertEquals('float', $settings['float_setting']['type']);
        self::assertEquals('boolean', $settings['boolean_setting']['type']);
        self::assertEquals('array', $settings['array_setting']['type']);

        // Verify default values are preserved with correct types
        self::assertEquals('text', $settings['string_setting']['default']);
        self::assertEquals('test@example.com', $settings['email_setting']['default']);
        self::assertEquals('https://example.com', $settings['url_setting']['default']);
        self::assertEquals('secret', $settings['password_setting']['default']);
        self::assertEquals('long text', $settings['textarea_setting']['default']);
        self::assertEquals(42, $settings['integer_setting']['default']);
        self::assertEquals(3.14, $settings['float_setting']['default']);
        self::assertTrue($settings['boolean_setting']['default']);
        self::assertEquals('["a","b","c"]', $settings['array_setting']['default']); // JSON string
    }

    public function testLoadDefinitionsWithComplexValidation(): void
    {
        $configData = [
            'settings' => [
                'complex_setting' => [
                    'type' => 'string',
                    'default' => 'test',
                    'validation' => [
                        'min_length' => 2,
                        'max_length' => 50,
                        'regex' => '/^[a-zA-Z]+$/',
                        'choices' => ['option1', 'option2', 'option3'],
                    ]
                ],
                'numeric_setting' => [
                    'type' => 'integer',
                    'default' => 10,
                    'validation' => [
                        'min' => 5,
                        'max' => 100,
                    ]
                ],
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);
        $settings = $service->getSettings();

        self::assertCount(2, $settings);

        // Test complex validation
        $validation = $settings['complex_setting']['validation'];
        self::assertEquals(2, $validation['min_length']);
        self::assertEquals(50, $validation['max_length']);
        self::assertEquals('/^[a-zA-Z]+$/', $validation['regex']);
        self::assertEquals(['option1', 'option2', 'option3'], $validation['choices']);

        // Test numeric validation
        $numericValidation = $settings['numeric_setting']['validation'];
        self::assertEquals(5, $numericValidation['min']);
        self::assertEquals(100, $numericValidation['max']);
    }

    public function testLoadDefinitionsWithEmptySettings(): void
    {
        $configData = [
            'settings' => []
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);
        $settings = $service->getSettings();

        self::assertIsArray($settings);
        self::assertEmpty($settings);
    }

    public function testLoadDefinitionsWithMissingSettingsKey(): void
    {
        $configData = [
            'other_config' => [
                'value' => 'test'
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);
        $definitions = $service->getSettings();

        self::assertIsArray($definitions);
        self::assertEmpty($definitions);
    }

    public function testLoadDefinitionsWithMissingDefinitionsKey(): void
    {
        $configData = [
            'settings' => []
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);
        $settings = $service->getSettings();

        self::assertIsArray($settings);
        self::assertEmpty($settings);
    }

    public function testLoadDefinitionsWithNonExistentConfigFile(): void
    {
        // Don't create the config file
        $service = new SettingsConfigService($this->tempDir);
        $settings = $service->getSettings();

        self::assertIsArray($settings);
        self::assertEmpty($settings);
    }

    public function testLoadDefinitionsWithInvalidYaml(): void
    {
        // Create invalid YAML content
        file_put_contents($this->configFile, "invalid: yaml: content: [\n  - incomplete");

        // This should handle the parsing error gracefully
        $this->expectException(\Throwable::class);
        new SettingsConfigService($this->tempDir);
    }

    public function testLoadDefinitionsWithInvalidConfigStructure(): void
    {
        $configData = [
            'settings' => [
                'invalid_key' => 'value' // This should trigger a configuration error
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        new SettingsConfigService($this->tempDir);
    }

    public function testLoadDefinitionsProcessesConfigurationCorrectly(): void
    {
        // Test that the Symfony Configuration component processes the config correctly
        $configData = [
            'settings' => [
                'test_setting' => [
                    'type' => 'string',
                    'default' => 'test_value',
                    'validation' => [
                        'min_length' => 1,
                    ]
                ]
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);
        $settings = $service->getSettings();

        // Verify the configuration was processed through SettingsConfiguration
        self::assertArrayHasKey('test_setting', $settings);
        self::assertEquals('string', $settings['test_setting']['type']);
        self::assertEquals('test_value', $settings['test_setting']['default']);
        self::assertArrayHasKey('validation', $settings['test_setting']);
        self::assertEquals(1, $settings['test_setting']['validation']['min_length']);
    }

    public function testGetDefinitionsReturnsSameDataMultipleTimes(): void
    {
        $configData = [
            'settings' => [
                'cached_setting' => [
                    'type' => 'string',
                    'default' => 'cached_value',
                ]
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);

        $settings1 = $service->getSettings();
        $settings2 = $service->getSettings();

        self::assertEquals($settings1, $settings2);
        self::assertSame($settings1, $settings2); // Should be the same reference
    }

    public function testConstructorLoadsDefinitionsImmediately(): void
    {
        $configData = [
            'settings' => [
                'immediate_setting' => [
                    'type' => 'boolean',
                    'default' => false,
                ]
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        // Definitions should be loaded during construction
        $service = new SettingsConfigService($this->tempDir);

        // Delete the config file after construction
        unlink($this->configFile);

        // Should still return the settings that were loaded during construction
        $settings = $service->getSettings();
        self::assertArrayHasKey('immediate_setting', $settings);
        self::assertEquals('boolean', $settings['immediate_setting']['type']);
        self::assertFalse($settings['immediate_setting']['default']);
    }

    public function testWithRealWorldConfig(): void
    {
        $configData = [
            'settings' => [
                'app_name' => [
                    'type' => 'string',
                    'default' => 'My Email Service',
                    'validation' => [
                        'min_length' => 1,
                        'max_length' => 100,
                    ]
                ],
                'registration_enabled' => [
                    'type' => 'boolean',
                    'default' => true,
                ],
                'max_quota_mb' => [
                    'type' => 'integer',
                    'default' => 1024,
                    'validation' => [
                        'min' => 100,
                        'max' => 10240,
                    ]
                ],
                'admin_email' => [
                    'type' => 'email',
                    'default' => 'admin@example.com',
                    'validation' => [
                        'min_length' => 5,
                        'max_length' => 100,
                    ]
                ],
                'support_url' => [
                    'type' => 'url',
                    'default' => 'https://support.example.com',
                ],
                'welcome_message' => [
                    'type' => 'textarea',
                    'default' => 'Welcome to our email service!',
                    'validation' => [
                        'max_length' => 1000,
                    ]
                ],
                'theme' => [
                    'type' => 'string',
                    'default' => 'default',
                    'validation' => [
                        'choices' => ['default', 'dark', 'light', 'custom'],
                    ]
                ],
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);
        $settings = $service->getSettings();

        self::assertCount(7, $settings);

        // Validate all real-world settings
        self::assertEquals('My Email Service', $settings['app_name']['default']);
        self::assertTrue($settings['registration_enabled']['default']);
        self::assertEquals(1024, $settings['max_quota_mb']['default']);
        self::assertEquals('admin@example.com', $settings['admin_email']['default']);
        self::assertEquals('https://support.example.com', $settings['support_url']['default']);
        self::assertEquals('Welcome to our email service!', $settings['welcome_message']['default']);
        self::assertEquals(['default', 'dark', 'light', 'custom'], $settings['theme']['validation']['choices']);
    }
}
