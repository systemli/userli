<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\SettingsType;
use App\Service\SettingsConfigService;
use App\Service\SettingsService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingsTypeTest extends TestCase
{
    private MockObject $configService;
    private MockObject $settingsService;
    private MockObject $formBuilder;
    private SettingsType $formType;

    protected function setUp(): void
    {
        $this->configService = $this->createMock(SettingsConfigService::class);
        $this->settingsService = $this->createMock(SettingsService::class);
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);

        $this->formType = new SettingsType($this->configService, $this->settingsService);
    }

    public function testBuildFormWithBasicSettings(): void
    {
        $definitions = [
            'app_name' => [
                'type' => 'string',
                'default' => 'Userli',
                'validation' => [
                    'min_length' => 1,
                    'max_length' => 255,
                ],
            ],
            'registration_enabled' => [
                'type' => 'boolean',
                'default' => true,
            ],
        ];

        $this->configService->expects($this->once())
            ->method('getSettings')
            ->willReturn($definitions);

        $this->settingsService->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['app_name', null, 'My App'],
                ['registration_enabled', null, false],
            ]);

        // Expected calls to add fields
        $this->formBuilder->expects($this->exactly(3))
            ->method('add')
            ->willReturnSelf();

        $this->formType->buildForm($this->formBuilder, []);
    }

    /**
     * @dataProvider fieldTypeProvider
     */
    public function testDetermineFieldType(string $type, array $validation, string $expectedClass): void
    {
        $reflection = new ReflectionClass($this->formType);
        $method = $reflection->getMethod('determineFieldType');
        $method->setAccessible(true);

        $result = $method->invoke($this->formType, $type, $validation);

        self::assertEquals($expectedClass, $result);
    }

    public function fieldTypeProvider(): array
    {
        return [
            'string type' => ['string', [], TextType::class],
            'email type' => ['email', [], EmailType::class],
            'url type' => ['url', [], UrlType::class],
            'password type' => ['password', [], PasswordType::class],
            'textarea type' => ['textarea', [], TextareaType::class],
            'integer type' => ['integer', [], IntegerType::class],
            'float type' => ['float', [], NumberType::class],
            'boolean type' => ['boolean', [], CheckboxType::class],
            'choice type' => ['string', ['choices' => ['a', 'b']], ChoiceType::class],
            'empty choices ignored' => ['string', ['choices' => []], TextType::class],
        ];
    }

    /**
     * @dataProvider constraintProvider
     */
    public function testBuildConstraints(array $validation, string $type, int $expectedCount): void
    {
        $reflection = new ReflectionClass($this->formType);
        $method = $reflection->getMethod('buildConstraints');
        $method->setAccessible(true);

        $result = $method->invoke($this->formType, $validation, $type);

        self::assertCount($expectedCount, $result);
    }

    public function constraintProvider(): array
    {
        return [
            'string type default' => [[], 'string', 1], // NotBlank constraint for string types
            'string with length' => [['min_length' => 1, 'max_length' => 10], 'string', 2], // NotBlank + Length constraint
            'string with only max length' => [['max_length' => 10], 'string', 2], // NotBlank + Length constraint
            'string with regex' => [['regex' => '/test/'], 'string', 1], // NotBlank (regex skipped due to delimiter issue)
            'integer with min/max' => [['min' => 0, 'max' => 100], 'integer', 3], // Type + Min + Max (no NotBlank for integers)
            'boolean type' => [[], 'boolean', 0], // No constraints for boolean types
            'choices' => [['choices' => ['a', 'b']], 'string', 2], // NotBlank + Choice constraint
            'empty choices ignored' => [['choices' => []], 'string', 1], // Only NotBlank constraint
            'url type' => [[], 'url', 2], // NotBlank + URL constraint
            'email type' => [[], 'email', 2], // NotBlank + Email constraint
            'password type' => [[], 'password', 1], // NotBlank constraint
            'textarea type' => [[], 'textarea', 1], // NotBlank constraint
        ];
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $options = $resolver->resolve([]);

        // Test basic options - just verify no exception is thrown
        self::assertIsArray($options);
    }

    public function testBuildFormCallsCorrectMethods(): void
    {
        $definitions = [
            'simple_setting' => [
                'type' => 'string',
                'default' => 'value',
            ],
        ];

        $this->configService->expects($this->once())
            ->method('getSettings')
            ->willReturn($definitions);

        $this->settingsService->expects($this->once())
            ->method('get')
            ->with('simple_setting')
            ->willReturn('current_value');

        $this->formBuilder->expects($this->exactly(2)) // setting + submit button
        ->method('add')
            ->willReturnSelf();

        $this->formType->buildForm($this->formBuilder, []);
    }

    public function testEmptyDefinitionsHandling(): void
    {
        $this->configService->expects($this->once())
            ->method('getSettings')
            ->willReturn([]);

        // Should only add submit button
        $this->formBuilder->expects($this->once())
            ->method('add')
            ->with('save')
            ->willReturnSelf();

        $this->formType->buildForm($this->formBuilder, []);
    }

    public function testChoiceFieldConfiguration(): void
    {
        $definitions = [
            'locale' => [
                'type' => 'string',
                'validation' => [
                    'choices' => ['en', 'de', 'fr'],
                ],
            ],
        ];

        $this->configService->expects($this->once())
            ->method('getSettings')
            ->willReturn($definitions);

        $this->settingsService->expects($this->once())
            ->method('get')
            ->willReturn('en');

        // Mock the add method to capture the field configuration
        $this->formBuilder->expects($this->exactly(2))
            ->method('add')
            ->willReturnCallback(function ($name, $type, $options = []) {
                if ($name === 'locale') {
                    self::assertEquals(ChoiceType::class, $type);
                    self::assertArrayHasKey('choices', $options);
                    // Choices are transformed to key-value pairs by Symfony
                    $expectedChoices = ['en' => 'en', 'de' => 'de', 'fr' => 'fr'];
                    self::assertEquals($expectedChoices, $options['choices']);
                }

                return $this->formBuilder;
            });

        $this->formType->buildForm($this->formBuilder, []);
    }

    public function testValidationConstraintsBuilding(): void
    {
        $validation = [
            'min_length' => 2,
            'max_length' => 10,
            'min' => 1,
            'max' => 100,
            'choices' => ['a', 'b', 'c'],
        ];

        $reflection = new ReflectionClass($this->formType);
        $method = $reflection->getMethod('buildConstraints');
        $method->setAccessible(true);

        $constraints = $method->invoke($this->formType, $validation, 'string');

        // Should contain NotBlank, Length, GreaterThanOrEqual, LessThanOrEqual, Choice
        self::assertCount(5, $constraints);
    }

    public function testBooleanFieldSpecialHandling(): void
    {
        $definitions = [
            'enabled' => [
                'type' => 'boolean',
                'default' => false,
            ],
        ];

        $this->configService->expects($this->once())
            ->method('getSettings')
            ->willReturn($definitions);

        $this->settingsService->expects($this->once())
            ->method('get')
            ->willReturn(true);

        $this->formBuilder->expects($this->exactly(2))
            ->method('add')
            ->willReturnCallback(function ($name, $type, $options = []) {
                if ($name === 'enabled') {
                    self::assertEquals(CheckboxType::class, $type);
                    self::assertTrue($options['data']);
                }

                return $this->formBuilder;
            });

        $this->formType->buildForm($this->formBuilder, []);
    }

    public function testNotBlankConstraintForStringTypes(): void
    {
        $reflection = new ReflectionClass($this->formType);
        $method = $reflection->getMethod('buildConstraints');
        $method->setAccessible(true);

        // Test string type: should always add NotBlank
        $validation = [];
        $constraints = $method->invoke($this->formType, $validation, 'string');
        self::assertCount(1, $constraints); // NotBlank

        // Test email type: should add NotBlank + Email
        $validation = [];
        $constraints = $method->invoke($this->formType, $validation, 'email');
        self::assertCount(2, $constraints); // NotBlank + Email

        // Test url type: should add NotBlank + URL
        $validation = [];
        $constraints = $method->invoke($this->formType, $validation, 'url');
        self::assertCount(2, $constraints); // NotBlank + URL

        // Test password type: should add NotBlank
        $validation = [];
        $constraints = $method->invoke($this->formType, $validation, 'password');
        self::assertCount(1, $constraints); // NotBlank

        // Test textarea type: should add NotBlank
        $validation = [];
        $constraints = $method->invoke($this->formType, $validation, 'textarea');
        self::assertCount(1, $constraints); // NotBlank

        // Test integer type: should NOT add NotBlank
        $validation = [];
        $constraints = $method->invoke($this->formType, $validation, 'integer');
        self::assertCount(1, $constraints); // Only Type constraint

        // Test boolean type: should NOT add NotBlank
        $validation = [];
        $constraints = $method->invoke($this->formType, $validation, 'boolean');
        self::assertCount(0, $constraints); // No constraints
    }
}
