<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Service\SettingsService;
use App\Twig\SettingsExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class SettingsExtensionTest extends TestCase
{
    public function testGetFunctionsReturnsSettingFunction(): void
    {
        $settings = $this->createStub(SettingsService::class);
        $extension = new SettingsExtension($settings);

        $functions = $extension->getFunctions();

        self::assertCount(1, $functions);
        self::assertInstanceOf(TwigFunction::class, $functions[0]);
        self::assertSame('setting', $functions[0]->getName());
    }

    public function testSettingFunctionCallsSettingsService(): void
    {
        $settings = $this->createMock(SettingsService::class);
        $settings->expects($this->once())
            ->method('get')
            ->with('app_name')
            ->willReturn('Userli');

        $extension = new SettingsExtension($settings);
        $functions = $extension->getFunctions();

        $callable = $functions[0]->getCallable();
        $result = $callable('app_name');

        self::assertSame('Userli', $result);
    }
}
