<?php

declare(strict_types=1);

namespace App\Tests\DependencyInjection\Configuration;

use App\DependencyInjection\Configuration\SettingsConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class SettingsConfigurationTest extends TestCase
{
    private SettingsConfiguration $configuration;
    private Processor $processor;

    protected function setUp(): void
    {
        $this->configuration = new SettingsConfiguration();
        $this->processor = new Processor();
    }

    public function testValidMinimalConfiguration(): void
    {
        $config = [
            'settings' => [
                'definitions' => [
                    'app_name' => [
                        'type' => 'string',
                        'default' => 'Userli',
                    ]
                ]
            ]
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config['settings']]);

        self::assertArrayHasKey('definitions', $processedConfig);
        self::assertArrayHasKey('app_name', $processedConfig['definitions']);
        self::assertEquals('string', $processedConfig['definitions']['app_name']['type']);
        self::assertEquals('Userli', $processedConfig['definitions']['app_name']['default']);
        // Validation array is only added when validation rules are specified
        self::assertArrayNotHasKey('validation', $processedConfig['definitions']['app_name']);
    }

    public function testDefaultValues(): void
    {
        $config = [
            'settings' => [
                'definitions' => [
                    'simple_setting' => []
                ]
            ]
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config['settings']]);

        $setting = $processedConfig['definitions']['simple_setting'];

        // Check default type
        self::assertEquals('string', $setting['type']);

        // Check default value is null
        self::assertNull($setting['default']);

        // Validation array is not present when no validation is specified
        self::assertArrayNotHasKey('validation', $setting);
    }

    public function testValidationDefaultValues(): void
    {
        $config = [
            'settings' => [
                'definitions' => [
                    'setting_with_validation' => [
                        'validation' => [] // Empty validation block
                    ]
                ]
            ]
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config['settings']]);

        $validation = $processedConfig['definitions']['setting_with_validation']['validation'];

        // Check validation defaults when validation block is present
        self::assertNull($validation['regex']);
        self::assertNull($validation['min_length']);
        self::assertNull($validation['max_length']);
        self::assertNull($validation['min']);
        self::assertNull($validation['max']);
        self::assertEquals([], $validation['choices']);
    }

    public function testAllSupportedTypes(): void
    {
        $supportedTypes = ['string', 'integer', 'boolean', 'array', 'float', 'email', 'url', 'password', 'textarea'];

        foreach ($supportedTypes as $type) {
            $config = [
                'settings' => [
                    'definitions' => [
                        "test_{$type}" => [
                            'type' => $type,
                            'default' => 'test_value',
                        ]
                    ]
                ]
            ];

            $processedConfig = $this->processor->processConfiguration($this->configuration, [$config['settings']]);

            self::assertEquals($type, $processedConfig['definitions']["test_{$type}"]['type']);
        }
    }

    public function testInvalidTypeThrowsException(): void
    {
        $config = [
            'settings' => [
                'definitions' => [
                    'invalid_setting' => [
                        'type' => 'invalid_type',
                    ]
                ]
            ]
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/The value "invalid_type" is not allowed for path "settings.definitions.invalid_setting.type"/');

        $this->processor->processConfiguration($this->configuration, [$config['settings']]);
    }

    public function testCompleteValidationConfiguration(): void
    {
        $config = [
            'settings' => [
                'definitions' => [
                    'email_setting' => [
                        'type' => 'email',
                        'default' => 'admin@example.org',
                        'validation' => [
                            'regex' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                            'min_length' => 5,
                            'max_length' => 100,
                        ]
                    ],
                    'choice_setting' => [
                        'type' => 'string',
                        'default' => 'en',
                        'validation' => [
                            'choices' => ['en', 'de', 'fr', 'es']
                        ]
                    ],
                    'numeric_setting' => [
                        'type' => 'integer',
                        'default' => 50,
                        'validation' => [
                            'min' => 0,
                            'max' => 100,
                        ]
                    ]
                ]
            ]
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config['settings']]);

        // Test email setting
        $emailSetting = $processedConfig['definitions']['email_setting'];
        self::assertEquals('email', $emailSetting['type']);
        self::assertEquals('admin@example.org', $emailSetting['default']);
        self::assertEquals('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $emailSetting['validation']['regex']);
        self::assertEquals(5, $emailSetting['validation']['min_length']);
        self::assertEquals(100, $emailSetting['validation']['max_length']);

        // Test choice setting
        $choiceSetting = $processedConfig['definitions']['choice_setting'];
        self::assertEquals('string', $choiceSetting['type']);
        self::assertEquals('en', $choiceSetting['default']);
        self::assertEquals(['en', 'de', 'fr', 'es'], $choiceSetting['validation']['choices']);

        // Test numeric setting
        $numericSetting = $processedConfig['definitions']['numeric_setting'];
        self::assertEquals('integer', $numericSetting['type']);
        self::assertEquals(50, $numericSetting['default']);
        self::assertEquals(0, $numericSetting['validation']['min']);
        self::assertEquals(100, $numericSetting['validation']['max']);
    }

    public function testEmptyDefinitionsAreAllowed(): void
    {
        $config = [
            'settings' => [
                'definitions' => []
            ]
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config['settings']]);

        self::assertArrayHasKey('definitions', $processedConfig);
        self::assertEquals([], $processedConfig['definitions']);
    }

    public function testValidationConstraintTypes(): void
    {
        $config = [
            'settings' => [
                'definitions' => [
                    'test_setting' => [
                        'validation' => [
                            'min_length' => 'invalid', // Should be integer
                        ]
                    ]
                ]
            ]
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/Invalid type for path "settings.definitions.test_setting.validation.min_length". Expected "?int", but got "?string"/');

        $this->processor->processConfiguration($this->configuration, [$config['settings']]);
    }

    public function testNestedArrayStructure(): void
    {
        $config = [
            'settings' => [
                'definitions' => [
                    'setting1' => [
                        'type' => 'string',
                        'default' => 'value1',
                    ],
                    'setting2' => [
                        'type' => 'boolean',
                        'default' => true,
                    ]
                ]
            ]
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config['settings']]);

        self::assertCount(2, $processedConfig['definitions']);
        self::assertArrayHasKey('setting1', $processedConfig['definitions']);
        self::assertArrayHasKey('setting2', $processedConfig['definitions']);

        self::assertEquals('string', $processedConfig['definitions']['setting1']['type']);
        self::assertEquals('boolean', $processedConfig['definitions']['setting2']['type']);
    }

    public function testUseAttributeAsKeyWorksCorrectly(): void
    {
        $config = [
            'settings' => [
                'definitions' => [
                    'custom_key' => [
                        'type' => 'string',
                        'default' => 'custom_value',
                    ]
                ]
            ]
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, [$config['settings']]);

        // The key should be preserved as the setting name
        self::assertArrayHasKey('custom_key', $processedConfig['definitions']);
        self::assertEquals('string', $processedConfig['definitions']['custom_key']['type']);
        self::assertEquals('custom_value', $processedConfig['definitions']['custom_key']['default']);
    }
}
