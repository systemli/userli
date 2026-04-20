<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Repository\AliasRepository;
use App\Service\RfcAliasResolver;
use App\Service\SettingsService;
use PHPUnit\Framework\TestCase;

class RfcAliasResolverTest extends TestCase
{
    public function testReturnsSettingDestinationForPostmaster(): void
    {
        $settingsService = $this->createMock(SettingsService::class);
        $settingsService->method('get')
            ->with('postmaster_address', '')
            ->willReturn('admin@example.org');

        $aliasRepository = $this->createMock(AliasRepository::class);
        $aliasRepository->expects(self::never())->method('findDestinationsBySource');

        $resolver = new RfcAliasResolver($aliasRepository, $settingsService);

        self::assertSame(['admin@example.org'], $resolver->resolveDestinations('postmaster@example.org'));
    }

    public function testReturnsSettingDestinationForAbuse(): void
    {
        $settingsService = $this->createMock(SettingsService::class);
        $settingsService->method('get')
            ->with('abuse_address', '')
            ->willReturn('abuse-team@example.org');

        $aliasRepository = $this->createMock(AliasRepository::class);
        $aliasRepository->expects(self::never())->method('findDestinationsBySource');

        $resolver = new RfcAliasResolver($aliasRepository, $settingsService);

        self::assertSame(['abuse-team@example.org'], $resolver->resolveDestinations('abuse@example.org'));
    }

    public function testFallsBackToRepositoryWhenSettingEmpty(): void
    {
        $settingsService = $this->createMock(SettingsService::class);
        $settingsService->method('get')
            ->with('postmaster_address', '')
            ->willReturn('');

        $aliasRepository = $this->createMock(AliasRepository::class);
        $aliasRepository->expects(self::once())
            ->method('findDestinationsBySource')
            ->with('postmaster@example.org')
            ->willReturn(['fallback@example.org']);

        $resolver = new RfcAliasResolver($aliasRepository, $settingsService);

        self::assertSame(['fallback@example.org'], $resolver->resolveDestinations('postmaster@example.org'));
    }

    public function testDelegatesNonRfcAddressToRepository(): void
    {
        $settingsService = $this->createMock(SettingsService::class);
        $settingsService->expects(self::never())->method('get');

        $aliasRepository = $this->createMock(AliasRepository::class);
        $aliasRepository->expects(self::once())
            ->method('findDestinationsBySource')
            ->with('info@example.org')
            ->willReturn(['user@example.org']);

        $resolver = new RfcAliasResolver($aliasRepository, $settingsService);

        self::assertSame(['user@example.org'], $resolver->resolveDestinations('info@example.org'));
    }

    public function testHandlesSourceWithoutAtSign(): void
    {
        $settingsService = $this->createMock(SettingsService::class);

        $aliasRepository = $this->createMock(AliasRepository::class);
        $aliasRepository->expects(self::once())
            ->method('findDestinationsBySource')
            ->with('nodomain')
            ->willReturn([]);

        $resolver = new RfcAliasResolver($aliasRepository, $settingsService);

        self::assertSame([], $resolver->resolveDestinations('nodomain'));
    }

    public function testIsRfcAddressReturnsTrueForPostmaster(): void
    {
        $resolver = new RfcAliasResolver(
            $this->createMock(AliasRepository::class),
            $this->createMock(SettingsService::class),
        );

        self::assertTrue($resolver->isRfcAddress('postmaster@example.org'));
        self::assertTrue($resolver->isRfcAddress('abuse@example.org'));
    }

    public function testIsRfcAddressReturnsFalseForRegularAddress(): void
    {
        $resolver = new RfcAliasResolver(
            $this->createMock(AliasRepository::class),
            $this->createMock(SettingsService::class),
        );

        self::assertFalse($resolver->isRfcAddress('info@example.org'));
        self::assertFalse($resolver->isRfcAddress('nodomain'));
    }
}
