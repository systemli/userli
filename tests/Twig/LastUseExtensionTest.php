<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Twig\LastUseExtension;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class LastUseExtensionTest extends TestCase
{
    public static function lastUseProvider(): iterable
    {
        yield 'this week' => ['2026-10-19', '2026-10-21 15:00', 'last-use.this-week'];
        yield 'last week' => ['2026-10-12', '2026-10-21 15:00', 'last-use.last-week'];
        yield 'last week in previous month' => ['2026-10-26', '2026-11-04 15:00', 'last-use.last-week'];
        yield 'weeks ago' => ['2026-10-05', '2026-10-21 15:00', 'last-use.weeks-ago:2'];
        yield 'weeks ago in previous month' => ['2026-09-21', '2026-10-21 15:00', 'last-use.weeks-ago:4'];
        yield 'one month ago' => ['2026-09-14', '2026-10-21 15:00', 'last-use.months-ago:1'];
        yield 'months ago' => ['2026-04-06', '2026-10-21 15:00', 'last-use.months-ago:6'];
        yield 'one year ago' => ['2025-10-20', '2026-10-21 15:00', 'last-use.years-ago:1'];
        yield 'years ago' => ['2023-03-06', '2026-10-21 15:00', 'last-use.years-ago:3'];
    }

    #[DataProvider('lastUseProvider')]
    public function testLastUse(string $time, string $now, string $expected): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $id, array $parameters = []): string => $id.(isset($parameters['%count%']) ? ':'.$parameters['%count%'] : '')
        );

        $extension = new LastUseExtension($translator);

        self::assertSame($expected, $extension->lastUse(new DateTimeImmutable($time), new DateTimeImmutable($now)));
    }
}
