<?php

declare(strict_types=1);

namespace App\Twig;

use DateTimeImmutable;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Attribute\AsTwigFilter;

/**
 * Turns a last login time, stored as the start of its week, into a vague phrase like "last week",
 * so users don't mistake the Monday for the actual day of use.
 */
final readonly class LastUseExtension
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[AsTwigFilter(name: 'last_use')]
    public function lastUse(DateTimeImmutable $time, ?DateTimeImmutable $now = null): string
    {
        $now ??= new DateTimeImmutable();
        $weeks = intdiv((int) $time->modify('monday this week midnight')->diff($now->modify('monday this week midnight'))->days, 7);

        if ($time > $now || 0 === $weeks) {
            return $this->translator->trans('last-use.this-week');
        }

        if (1 === $weeks) {
            return $this->translator->trans('last-use.last-week');
        }

        if ($weeks < 5) {
            return $this->translator->trans('last-use.weeks-ago', ['%count%' => $weeks]);
        }

        $months = ((int) $now->format('Y') * 12 + (int) $now->format('n')) - ((int) $time->format('Y') * 12 + (int) $time->format('n'));

        if ($months < 12) {
            return $this->translator->trans('last-use.months-ago', ['%count%' => $months]);
        }

        return $this->translator->trans('last-use.years-ago', ['%count%' => intdiv($months, 12)]);
    }
}
