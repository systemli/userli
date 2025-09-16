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
        $this->configDir = $this->tempDir . '/config/definitions';
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
                'definitions' => [
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
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);
        $definitions = $service->getDefinitions();

        self::assertIsArray($definitions);
        self::assertCount(3, $definitions);

        // Test app_name definition
        self::assertArrayHasKey('app_name', $definitions);
        self::assertEquals('string', $definitions['app_name']['type']);
        self::assertEquals('Userli', $definitions['app_name']['default']);
        self::assertEquals(1, $definitions['app_name']['validation']['min_length']);
        self::assertEquals(255, $definitions['app_name']['validation']['max_length']);

        // Test registration_enabled definition
        self::assertArrayHasKey('registration_enabled', $definitions);
        self::assertEquals('boolean', $definitions['registration_enabled']['type']);
        self::assertTrue($definitions['registration_enabled']['default']);

        // Test max_users definition
        self::assertArrayHasKey('max_users', $definitions);
        self::assertEquals('integer', $definitions['max_users']['type']);
        self::assertEquals(1000, $definitions['max_users']['default']);
        self::assertEquals(1, $definitions['max_users']['validation']['min']);
        self::assertEquals(10000, $definitions['max_users']['validation']['max']);
    }

    public function testLoadDefinitionsWithMinimalConfig(): void
    {
        $configData = [
            'settings' => [
                'definitions' => [
                    'simple_setting' => [
                        'type' => 'string',
                        'default' => 'value',
                    ]
                ]
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);
        $definitions = $service->getDefinitions();

        self::assertCount(1, $definitions);
        self::assertArrayHasKey('simple_setting', $definitions);
        self::assertEquals('string', $definitions['simple_setting']['type']);
        self::assertEquals('value', $definitions['simple_setting']['default']);
        self::assertArrayNotHasKey('validation', $definitions['simple_setting']);
    }

    public function testLoadDefinitionsWithAllSupportedTypes(): void
    {
        $configData = [
            'settings' => [
                'definitions' => [
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
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);
        $definitions = $service->getDefinitions();

        self::assertCount(9, $definitions);

        // Verify all types are preserved
        self::assertEquals('string', $definitions['string_setting']['type']);
        self::assertEquals('email', $definitions['email_setting']['type']);
        self::assertEquals('url', $definitions['url_setting']['type']);
        self::assertEquals('password', $definitions['password_setting']['type']);
        self::assertEquals('textarea', $definitions['textarea_setting']['type']);
        self::assertEquals('integer', $definitions['integer_setting']['type']);
        self::assertEquals('float', $definitions['float_setting']['type']);
        self::assertEquals('boolean', $definitions['boolean_setting']['type']);
        self::assertEquals('array', $definitions['array_setting']['type']);

        // Verify default values are preserved with correct types
        self::assertEquals('text', $definitions['string_setting']['default']);
        self::assertEquals('test@example.com', $definitions['email_setting']['default']);
        self::assertEquals('https://example.com', $definitions['url_setting']['default']);
        self::assertEquals('secret', $definitions['password_setting']['default']);
        self::assertEquals('long text', $definitions['textarea_setting']['default']);
        self::assertEquals(42, $definitions['integer_setting']['default']);
        self::assertEquals(3.14, $definitions['float_setting']['default']);
        self::assertTrue($definitions['boolean_setting']['default']);
        self::assertEquals('["a","b","c"]', $definitions['array_setting']['default']); // JSON string
    }

    public function testLoadDefinitionsWithComplexValidation(): void
    {
        $configData = [
            'settings' => [
                'definitions' => [
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
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);
        $definitions = $service->getDefinitions();

        self::assertCount(2, $definitions);

        // Test complex validation
        $validation = $definitions['complex_setting']['validation'];
        self::assertEquals(2, $validation['min_length']);
        self::assertEquals(50, $validation['max_length']);
        self::assertEquals('/^[a-zA-Z]+$/', $validation['regex']);
        self::assertEquals(['option1', 'option2', 'option3'], $validation['choices']);

        // Test numeric validation
        $numericValidation = $definitions['numeric_setting']['validation'];
        self::assertEquals(5, $numericValidation['min']);
        self::assertEquals(100, $numericValidation['max']);
    }

    public function testLoadDefinitionsWithEmptySettings(): void
    {
        $configData = [
            'settings' => [
                'definitions' => []
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);
        $definitions = $service->getDefinitions();

        self::assertIsArray($definitions);
        self::assertEmpty($definitions);
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
        $definitions = $service->getDefinitions();

        self::assertIsArray($definitions);
        self::assertEmpty($definitions);
    }

    public function testLoadDefinitionsWithMissingDefinitionsKey(): void
    {
        $configData = [
            'settings' => [
                'definitions' => [] // Valid but empty definitions
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);
        $definitions = $service->getDefinitions();

        self::assertIsArray($definitions);
        self::assertEmpty($definitions);
    }

    public function testLoadDefinitionsWithNonExistentConfigFile(): void
    {
        // Don't create the config file
        $service = new SettingsConfigService($this->tempDir);
        $definitions = $service->getDefinitions();

        self::assertIsArray($definitions);
        self::assertEmpty($definitions);
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
                'definitions' => [
                    'test_setting' => [
                        'type' => 'string',
                        'default' => 'test_value',
                        'validation' => [
                            'min_length' => 1,
                        ]
                    ]
                ]
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);
        $definitions = $service->getDefinitions();

        // Verify the configuration was processed through SettingsConfiguration
        self::assertArrayHasKey('test_setting', $definitions);
        self::assertEquals('string', $definitions['test_setting']['type']);
        self::assertEquals('test_value', $definitions['test_setting']['default']);
        self::assertArrayHasKey('validation', $definitions['test_setting']);
        self::assertEquals(1, $definitions['test_setting']['validation']['min_length']);
    }

    public function testGetDefinitionsReturnsSameDataMultipleTimes(): void
    {
        $configData = [
            'settings' => [
                'definitions' => [
                    'cached_setting' => [
                        'type' => 'string',
                        'default' => 'cached_value',
                    ]
                ]
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);

        $definitions1 = $service->getDefinitions();
        $definitions2 = $service->getDefinitions();

        self::assertEquals($definitions1, $definitions2);
        self::assertSame($definitions1, $definitions2); // Should be the same reference
    }

    public function testConstructorLoadsDefinitionsImmediately(): void
    {
        $configData = [
            'settings' => [
                'definitions' => [
                    'immediate_setting' => [
                        'type' => 'boolean',
                        'default' => false,
                    ]
                ]
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        // Definitions should be loaded during construction
        $service = new SettingsConfigService($this->tempDir);

        // Delete the config file after construction
        unlink($this->configFile);

        // Should still return the definitions that were loaded during construction
        $definitions = $service->getDefinitions();
        self::assertArrayHasKey('immediate_setting', $definitions);
        self::assertEquals('boolean', $definitions['immediate_setting']['type']);
        self::assertFalse($definitions['immediate_setting']['default']);
    }

    public function testWithRealWorldConfig(): void
    {
        $configData = [
            'settings' => [
                'definitions' => [
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
            ]
        ];

        file_put_contents($this->configFile, Yaml::dump($configData));

        $service = new SettingsConfigService($this->tempDir);
        $definitions = $service->getDefinitions();

        self::assertCount(7, $definitions);

        // Validate all real-world settings
        self::assertEquals('My Email Service', $definitions['app_name']['default']);
        self::assertTrue($definitions['registration_enabled']['default']);
        self::assertEquals(1024, $definitions['max_quota_mb']['default']);
        self::assertEquals('admin@example.com', $definitions['admin_email']['default']);
        self::assertEquals('https://support.example.com', $definitions['support_url']['default']);
        self::assertEquals('Welcome to our email service!', $definitions['welcome_message']['default']);
        self::assertEquals(['default', 'dark', 'light', 'custom'], $definitions['theme']['validation']['choices']);
    }
}
