<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use App\Service\SettingsConfigService;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Contracts\Cache\CacheInterface;

class SettingsServiceTest extends TestCase
{
    private Stub $repository;
    private Stub $entityManager;
    private Stub $cache;
    private Stub $configService;
    private SettingsService $settingsService;

    protected function setUp(): void
    {
        $this->repository = $this->createStub(SettingRepository::class);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->cache = $this->createStub(CacheInterface::class);
        $this->configService = $this->createStub(SettingsConfigService::class);

        $this->settingsService = new SettingsService(
            $this->repository,
            $this->entityManager,
            $this->cache,
            $this->configService
        );
    }

    public function testSetNewSetting(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => 'new_setting'])
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Setting::class));

        $entityManager->expects($this->once())
            ->method('flush');

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('delete')
            ->with('app.settings');

        $settingsService = new SettingsService(
            $repository,
            $entityManager,
            $cache,
            $this->configService
        );

        $settingsService->set('new_setting', 'test_value');
    }

    public function testSetExistingSetting(): void
    {
        $setting = $this->createMock(Setting::class);
        $setting->expects($this->once())
            ->method('setValue')
            ->with('test_value');

        $repository = $this->createMock(SettingRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => 'existing_setting'])
            ->willReturn($setting);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())
            ->method('persist');

        $entityManager->expects($this->once())
            ->method('flush');

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('delete')
            ->with('app.settings');

        $settingsService = new SettingsService(
            $repository,
            $entityManager,
            $cache,
            $this->configService
        );

        $settingsService->set('existing_setting', 'test_value');
    }

    public function testSetWithBooleanValue(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(static function (Setting $setting) {
                return $setting->getName() === 'bool_setting' && $setting->getValue() === '1';
            }));

        $entityManager->expects($this->once())
            ->method('flush');

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('delete');

        $settingsService = new SettingsService(
            $repository,
            $entityManager,
            $cache,
            $this->configService
        );

        $settingsService->set('bool_setting', true);
    }

    public function testSetWithArrayValue(): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(static function (Setting $setting) {
                return $setting->getName() === 'array_setting'
                    && $setting->getValue() === '["a","b","c"]';
            }));

        $entityManager->expects($this->once())
            ->method('flush');

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('delete');

        $settingsService = new SettingsService(
            $repository,
            $entityManager,
            $cache,
            $this->configService
        );

        $settingsService->set('array_setting', ['a', 'b', 'c']);
    }

    public function testSetAll(): void
    {
        $configService = $this->createMock(SettingsConfigService::class);
        $configService->expects($this->once())
            ->method('getSettings')
            ->willReturn([
                'setting1' => ['type' => 'string'],
                'setting2' => ['type' => 'integer'],
                'setting3' => ['type' => 'boolean'],
            ]);

        $existingSetting = $this->createMock(Setting::class);
        $existingSetting->expects($this->once())
            ->method('setValue')
            ->with('updated_value');

        $callCount = 0;
        $expectedArgs = [
            ['name' => 'setting1'],
            ['name' => 'setting2'],
            ['name' => 'setting3'],
        ];
        $returnValues = [
            null,              // setting1 doesn't exist
            $existingSetting,  // setting2 exists
            null,              // setting3 doesn't exist
        ];

        $repository = $this->createMock(SettingRepository::class);
        $repository->expects($this->exactly(3))
            ->method('findOneBy')
            ->willReturnCallback(static function (array $criteria) use (&$callCount, $expectedArgs, $returnValues) {
                $expected = $expectedArgs[$callCount];
                $returnValue = $returnValues[$callCount];
                ++$callCount;
                self::assertSame($expected, $criteria);

                return $returnValue;
            });

        // Should persist 2 new settings (setting1 and setting3), setting2 already exists
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(2))
            ->method('persist')
            ->with($this->isInstanceOf(Setting::class));

        $entityManager->expects($this->once())
            ->method('flush');

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('delete')
            ->with('app.settings');

        $settingsService = new SettingsService(
            $repository,
            $entityManager,
            $cache,
            $configService
        );

        $settingsService->setAll([
            'setting1' => 'new_value',
            'setting2' => 'updated_value',
            'setting3' => true,
            'undefined_setting' => 'ignored', // Should be skipped
        ]);
    }

    public function testSetAllSkipsUndefinedSettings(): void
    {
        $configService = $this->createMock(SettingsConfigService::class);
        $configService->expects($this->once())
            ->method('getSettings')
            ->willReturn([
                'defined_setting' => ['type' => 'string'],
            ]);

        $repository = $this->createMock(SettingRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => 'defined_setting'])
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('persist');

        $entityManager->expects($this->once())
            ->method('flush');

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('delete');

        $settingsService = new SettingsService(
            $repository,
            $entityManager,
            $cache,
            $configService
        );

        $settingsService->setAll([
            'defined_setting' => 'value',
            'undefined_setting' => 'ignored',
        ]);
    }

    public function testClearCache(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('delete')
            ->with('app.settings');

        $settingsService = new SettingsService(
            $this->repository,
            $this->entityManager,
            $cache,
            $this->configService
        );

        $settingsService->clearCache();
    }

    #[DataProvider('valueToStringProvider')]
    public function testValueToStringConversion(mixed $input, string $expected): void
    {
        $repository = $this->createMock(SettingRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(static function (Setting $setting) use ($expected) {
                return $setting->getValue() === $expected;
            }));

        $entityManager->expects($this->once())
            ->method('flush');

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('delete');

        $settingsService = new SettingsService(
            $repository,
            $entityManager,
            $cache,
            $this->configService
        );

        $settingsService->set('test_setting', $input);
    }

    public static function valueToStringProvider(): array
    {
        return [
            'null value' => [null, ''],
            'true boolean' => [true, '1'],
            'false boolean' => [false, '0'],
            'string value' => ['test', 'test'],
            'integer value' => [42, '42'],
            'float value' => [3.14, '3.14'],
            'array value' => [['a', 'b'], '["a","b"]'],
        ];
    }

    public function testConvertValueFromStringWithString(): void
    {
        $configService = $this->createMock(SettingsConfigService::class);
        $configService->expects($this->once())
            ->method('getSettings')
            ->willReturn([
                'test_setting' => ['type' => 'string'],
            ]);

        $settingsService = new SettingsService(
            $this->repository,
            $this->entityManager,
            $this->cache,
            $configService
        );

        $reflection = new ReflectionClass($settingsService);
        $method = $reflection->getMethod('convertValueFromString');
        $method->setAccessible(true);

        $result = $method->invoke($settingsService, 'test_setting', 'test_value');

        self::assertEquals('test_value', $result);
    }

    public function testConvertValueFromStringWithInteger(): void
    {
        $configService = $this->createMock(SettingsConfigService::class);
        $configService->expects($this->once())
            ->method('getSettings')
            ->willReturn([
                'test_setting' => ['type' => 'integer'],
            ]);

        $settingsService = new SettingsService(
            $this->repository,
            $this->entityManager,
            $this->cache,
            $configService
        );

        $reflection = new ReflectionClass($settingsService);
        $method = $reflection->getMethod('convertValueFromString');
        $method->setAccessible(true);

        $result = $method->invoke($settingsService, 'test_setting', '42');

        self::assertEquals(42, $result);
        self::assertIsInt($result);
    }

    public function testConvertValueFromStringWithFloat(): void
    {
        $configService = $this->createMock(SettingsConfigService::class);
        $configService->expects($this->once())
            ->method('getSettings')
            ->willReturn([
                'test_setting' => ['type' => 'float'],
            ]);

        $settingsService = new SettingsService(
            $this->repository,
            $this->entityManager,
            $this->cache,
            $configService
        );

        $reflection = new ReflectionClass($settingsService);
        $method = $reflection->getMethod('convertValueFromString');
        $method->setAccessible(true);

        $result = $method->invoke($settingsService, 'test_setting', '3.14');

        self::assertEquals(3.14, $result);
        self::assertIsFloat($result);
    }

    public function testConvertValueFromStringWithBoolean(): void
    {
        $configService = $this->createMock(SettingsConfigService::class);
        $configService->expects($this->once())
            ->method('getSettings')
            ->willReturn([
                'test_setting' => ['type' => 'boolean'],
            ]);

        $settingsService = new SettingsService(
            $this->repository,
            $this->entityManager,
            $this->cache,
            $configService
        );

        $reflection = new ReflectionClass($settingsService);
        $method = $reflection->getMethod('convertValueFromString');
        $method->setAccessible(true);

        $result = $method->invoke($settingsService, 'test_setting', '1');

        self::assertTrue($result);
    }

    public function testConvertValueFromStringWithArray(): void
    {
        $configService = $this->createMock(SettingsConfigService::class);
        $configService->expects($this->once())
            ->method('getSettings')
            ->willReturn([
                'test_setting' => ['type' => 'array'],
            ]);

        $settingsService = new SettingsService(
            $this->repository,
            $this->entityManager,
            $this->cache,
            $configService
        );

        $reflection = new ReflectionClass($settingsService);
        $method = $reflection->getMethod('convertValueFromString');
        $method->setAccessible(true);

        $result = $method->invoke($settingsService, 'test_setting', '["a","b","c"]');

        self::assertEquals(['a', 'b', 'c'], $result);
        self::assertIsArray($result);
    }

    public function testConvertValueFromStringWithEmptyString(): void
    {
        $configService = $this->createMock(SettingsConfigService::class);
        $configService->expects($this->once())
            ->method('getSettings')
            ->willReturn([
                'test_setting' => ['type' => 'string'],
            ]);

        $settingsService = new SettingsService(
            $this->repository,
            $this->entityManager,
            $this->cache,
            $configService
        );

        $reflection = new ReflectionClass($settingsService);
        $method = $reflection->getMethod('convertValueFromString');
        $method->setAccessible(true);

        $result = $method->invoke($settingsService, 'test_setting', '');

        self::assertNull($result);
    }

    public function testConvertValueFromStringWithAlreadyCorrectType(): void
    {
        $configService = $this->createMock(SettingsConfigService::class);
        $configService->expects($this->never())
            ->method('getSettings');

        $settingsService = new SettingsService(
            $this->repository,
            $this->entityManager,
            $this->cache,
            $configService
        );

        $reflection = new ReflectionClass($settingsService);
        $method = $reflection->getMethod('convertValueFromString');
        $method->setAccessible(true);

        $result = $method->invoke($settingsService, 'test_setting', null);

        self::assertNull($result);
    }

    public function testConvertValueFromStringWithBooleanAlreadyCorrect(): void
    {
        $configService = $this->createMock(SettingsConfigService::class);
        $configService->expects($this->once())
            ->method('getSettings')
            ->willReturn([
                'test_setting' => ['type' => 'boolean'],
            ]);

        $settingsService = new SettingsService(
            $this->repository,
            $this->entityManager,
            $this->cache,
            $configService
        );

        $reflection = new ReflectionClass($settingsService);
        $method = $reflection->getMethod('convertValueFromString');
        $method->setAccessible(true);

        $result = $method->invoke($settingsService, 'test_setting', true);

        self::assertTrue($result);
    }

    #[DataProvider('booleanConversionProvider')]
    public function testBooleanStringConversion(mixed $input, bool $expected): void
    {
        $configService = $this->createMock(SettingsConfigService::class);
        $configService->expects($this->once())
            ->method('getSettings')
            ->willReturn([
                'test_setting' => ['type' => 'boolean'],
            ]);

        $settingsService = new SettingsService(
            $this->repository,
            $this->entityManager,
            $this->cache,
            $configService
        );

        $reflection = new ReflectionClass($settingsService);
        $method = $reflection->getMethod('convertValueFromString');
        $method->setAccessible(true);

        $result = $method->invoke($settingsService, 'test_setting', $input);

        self::assertEquals($expected, $result);
        self::assertIsBool($result);
    }

    public static function booleanConversionProvider(): array
    {
        return [
            'string true' => ['true', true],
            'string 1' => ['1', true],
            'integer 1' => [1, true],
            'string false' => ['false', false],
            'string 0' => ['0', false],
            'integer 0' => [0, false],
            'other string' => ['other', false],
        ];
    }

    public function testConvertValueToStringPrivateMethod(): void
    {
        $reflection = new ReflectionClass($this->settingsService);
        $method = $reflection->getMethod('convertValueToString');
        $method->setAccessible(true);

        // Test null
        $result = $method->invoke($this->settingsService, null);
        self::assertEquals('', $result);

        // Test boolean true
        $result = $method->invoke($this->settingsService, true);
        self::assertEquals('1', $result);

        // Test boolean false
        $result = $method->invoke($this->settingsService, false);
        self::assertEquals('0', $result);

        // Test array
        $result = $method->invoke($this->settingsService, ['a', 'b']);
        self::assertEquals('["a","b"]', $result);

        // Test string
        $result = $method->invoke($this->settingsService, 'test');
        self::assertEquals('test', $result);

        // Test integer
        $result = $method->invoke($this->settingsService, 42);
        self::assertEquals('42', $result);
    }

    public function testGetFromDatabase(): void
    {
        $configService = $this->createMock(SettingsConfigService::class);

        // Create a partial mock of SettingsService to mock getAllSettings()
        $settingsService = $this->getMockBuilder(SettingsService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->entityManager,
                $this->cache,
                $configService,
            ])
            ->onlyMethods(['getAllSettings'])
            ->getMock();

        $settingsService->expects($this->once())
            ->method('getAllSettings')
            ->willReturn(['test_setting' => 'database_value']);

        $configService->expects($this->once())
            ->method('getSettings')
            ->willReturn([
                'test_setting' => ['type' => 'string'],
            ]);

        $result = $settingsService->get('test_setting');

        self::assertEquals('database_value', $result);
    }

    public function testGetFromDefaultDefinition(): void
    {
        $configService = $this->createMock(SettingsConfigService::class);

        // Create a partial mock of SettingsService to mock getAllSettings()
        $settingsService = $this->getMockBuilder(SettingsService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->entityManager,
                $this->cache,
                $configService,
            ])
            ->onlyMethods(['getAllSettings'])
            ->getMock();

        $settingsService->expects($this->once())
            ->method('getAllSettings')
            ->willReturn([]); // Empty database

        $configService->expects($this->exactly(2))
            ->method('getSettings')
            ->willReturn([
                'test_setting' => [
                    'type' => 'string',
                    'default' => 'default_value',
                ],
            ]);

        $result = $settingsService->get('test_setting');

        self::assertEquals('default_value', $result);
    }

    public function testGetWithFallbackParameter(): void
    {
        $configService = $this->createMock(SettingsConfigService::class);

        // Create a partial mock of SettingsService to mock getAllSettings()
        $settingsService = $this->getMockBuilder(SettingsService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->entityManager,
                $this->cache,
                $configService,
            ])
            ->onlyMethods(['getAllSettings'])
            ->getMock();

        $settingsService->expects($this->once())
            ->method('getAllSettings')
            ->willReturn([]);

        $configService->expects($this->exactly(2))
            ->method('getSettings')
            ->willReturn([
                'test_setting' => [
                    'type' => 'string',
                    // No default value in definition
                ],
            ]);

        $result = $settingsService->get('test_setting', 'fallback_value');

        self::assertEquals('fallback_value', $result);
    }

    public function testGetWithNoSettingAnywhere(): void
    {
        $configService = $this->createMock(SettingsConfigService::class);

        // Create a partial mock of SettingsService to mock getAllSettings()
        $settingsService = $this->getMockBuilder(SettingsService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->entityManager,
                $this->cache,
                $configService,
            ])
            ->onlyMethods(['getAllSettings'])
            ->getMock();

        $settingsService->expects($this->once())
            ->method('getAllSettings')
            ->willReturn([]);

        $configService->expects($this->once())
            ->method('getSettings')
            ->willReturn([]); // No definition exists

        $result = $settingsService->get('nonexistent_setting');

        self::assertNull($result);
    }

    public function testGetWithTypeConversion(): void
    {
        $configService = $this->createMock(SettingsConfigService::class);

        // Create a partial mock of SettingsService to mock getAllSettings()
        $settingsService = $this->getMockBuilder(SettingsService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->entityManager,
                $this->cache,
                $configService,
            ])
            ->onlyMethods(['getAllSettings'])
            ->getMock();

        $settingsService->expects($this->once())
            ->method('getAllSettings')
            ->willReturn(['bool_setting' => '1']); // String from database

        $configService->expects($this->once())
            ->method('getSettings')
            ->willReturn([
                'bool_setting' => ['type' => 'boolean'],
            ]);

        $result = $settingsService->get('bool_setting');

        self::assertTrue($result);
        self::assertIsBool($result);
    }

    public function testGetWithDefaultTypeConversion(): void
    {
        $configService = $this->createMock(SettingsConfigService::class);

        // Create a partial mock of SettingsService to mock getAllSettings()
        $settingsService = $this->getMockBuilder(SettingsService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->entityManager,
                $this->cache,
                $configService,
            ])
            ->onlyMethods(['getAllSettings'])
            ->getMock();

        $settingsService->expects($this->once())
            ->method('getAllSettings')
            ->willReturn([]);

        $configService->expects($this->exactly(2))
            ->method('getSettings')
            ->willReturn([
                'int_setting' => [
                    'type' => 'integer',
                    'default' => 42, // Already correct type
                ],
            ]);

        $result = $settingsService->get('int_setting');

        self::assertEquals(42, $result);
        self::assertIsInt($result);
    }
}
