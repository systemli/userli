<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Repository\DomainRepository;
use App\Service\MtaStsService;
use App\Service\SettingsService;
use PHPUnit\Framework\TestCase;

class MtaStsServiceTest extends TestCase
{
    private DomainRepository $domainRepository;
    private SettingsService $settingsService;
    private MtaStsService $service;

    protected function setUp(): void
    {
        $this->domainRepository = $this->createMock(DomainRepository::class);
        $this->settingsService = $this->createMock(SettingsService::class);

        $this->service = new MtaStsService(
            $this->domainRepository,
            $this->settingsService,
        );
    }

    public function testGetPolicyReturnsNullForNonMtaStsHost(): void
    {
        self::assertNull($this->service->getPolicy('example.org'));
    }

    public function testGetPolicyReturnsNullForEmptyDomainAfterPrefix(): void
    {
        self::assertNull($this->service->getPolicy('mta-sts.'));
    }

    public function testGetPolicyReturnsNullForUnknownDomain(): void
    {
        $this->domainRepository->method('existsByName')
            ->with('unknown.org')
            ->willReturn(false);

        self::assertNull($this->service->getPolicy('mta-sts.unknown.org'));
    }

    public function testGetPolicyReturnsNullWhenMxEmptyAndModeNotNone(): void
    {
        $this->domainRepository->method('existsByName')
            ->with('example.org')
            ->willReturn(true);

        $this->settingsService->method('get')
            ->willReturnMap([
                ['mta_sts_mode', 'testing', 'enforce'],
                ['mta_sts_mx', '', ''],
                ['mta_sts_max_age', 604800, 604800],
            ]);

        self::assertNull($this->service->getPolicy('mta-sts.example.org'));
    }

    public function testGetPolicyReturnsEnforcePolicy(): void
    {
        $this->domainRepository->method('existsByName')
            ->with('example.org')
            ->willReturn(true);

        $this->settingsService->method('get')
            ->willReturnMap([
                ['mta_sts_mode', 'testing', 'enforce'],
                ['mta_sts_mx', '', "mail.example.org\nbackup.example.org"],
                ['mta_sts_max_age', 604800, 604800],
            ]);

        $expected = "version: STSv1\r\nmode: enforce\r\nmx: mail.example.org\r\nmx: backup.example.org\r\nmax_age: 604800\r\n";
        self::assertSame($expected, $this->service->getPolicy('mta-sts.example.org'));
    }

    public function testGetPolicyReturnsTestingPolicy(): void
    {
        $this->domainRepository->method('existsByName')
            ->with('example.org')
            ->willReturn(true);

        $this->settingsService->method('get')
            ->willReturnMap([
                ['mta_sts_mode', 'testing', 'testing'],
                ['mta_sts_mx', '', 'mail.example.org'],
                ['mta_sts_max_age', 604800, 86400],
            ]);

        $expected = "version: STSv1\r\nmode: testing\r\nmx: mail.example.org\r\nmax_age: 86400\r\n";
        self::assertSame($expected, $this->service->getPolicy('mta-sts.example.org'));
    }

    public function testGetPolicyReturnsPolicyWithModeNone(): void
    {
        $this->domainRepository->method('existsByName')
            ->with('example.org')
            ->willReturn(true);

        $this->settingsService->method('get')
            ->willReturnMap([
                ['mta_sts_mode', 'testing', 'none'],
                ['mta_sts_mx', '', 'mail.example.org'],
                ['mta_sts_max_age', 604800, 604800],
            ]);

        $expected = "version: STSv1\r\nmode: none\r\nmx: mail.example.org\r\nmax_age: 604800\r\n";
        self::assertSame($expected, $this->service->getPolicy('mta-sts.example.org'));
    }

    public function testGetPolicyReturnsPolicyWithModeNoneAndEmptyMx(): void
    {
        $this->domainRepository->method('existsByName')
            ->with('example.org')
            ->willReturn(true);

        $this->settingsService->method('get')
            ->willReturnMap([
                ['mta_sts_mode', 'testing', 'none'],
                ['mta_sts_mx', '', ''],
                ['mta_sts_max_age', 604800, 604800],
            ]);

        $expected = "version: STSv1\r\nmode: none\r\nmax_age: 604800\r\n";
        self::assertSame($expected, $this->service->getPolicy('mta-sts.example.org'));
    }

    public function testGetPolicyTrimsBlankLinesFromMx(): void
    {
        $this->domainRepository->method('existsByName')
            ->with('example.org')
            ->willReturn(true);

        $this->settingsService->method('get')
            ->willReturnMap([
                ['mta_sts_mode', 'testing', 'enforce'],
                ['mta_sts_mx', '', "mail.example.org\n\n  \nbackup.example.org\n"],
                ['mta_sts_max_age', 604800, 604800],
            ]);

        $expected = "version: STSv1\r\nmode: enforce\r\nmx: mail.example.org\r\nmx: backup.example.org\r\nmax_age: 604800\r\n";
        self::assertSame($expected, $this->service->getPolicy('mta-sts.example.org'));
    }

    public function testGetPolicyHandlesUppercaseHost(): void
    {
        $this->domainRepository->method('existsByName')
            ->with('example.org')
            ->willReturn(true);

        $this->settingsService->method('get')
            ->willReturnMap([
                ['mta_sts_mode', 'testing', 'enforce'],
                ['mta_sts_mx', '', 'mail.example.org'],
                ['mta_sts_max_age', 604800, 604800],
            ]);

        self::assertNotNull($this->service->getPolicy('MTA-STS.EXAMPLE.ORG'));
    }

    public function testGetPolicyHandlesCrlfInMxTextarea(): void
    {
        $this->domainRepository->method('existsByName')
            ->with('example.org')
            ->willReturn(true);

        $this->settingsService->method('get')
            ->willReturnMap([
                ['mta_sts_mode', 'testing', 'enforce'],
                ['mta_sts_mx', '', "mail.example.org\r\nbackup.example.org"],
                ['mta_sts_max_age', 604800, 604800],
            ]);

        $expected = "version: STSv1\r\nmode: enforce\r\nmx: mail.example.org\r\nmx: backup.example.org\r\nmax_age: 604800\r\n";
        self::assertSame($expected, $this->service->getPolicy('mta-sts.example.org'));
    }
}
