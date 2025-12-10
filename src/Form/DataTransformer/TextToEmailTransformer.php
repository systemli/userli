<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use Override;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements DataTransformerInterface<string, string>
 */
final class TextToEmailTransformer implements DataTransformerInterface
{
    /**
     * Constructor.
     */
    public function __construct(private readonly string $domain)
    {
    }

    #[Override]
    public function transform(mixed $value): mixed
    {
        if (null === $value) {
            return '';
        }

        $pos = strpos((string) $value, '@');

        return false === $pos ? (string) $value : substr((string) $value, 0, $pos);
    }

    #[Override]
    public function reverseTransform(mixed $value): mixed
    {
        if (null === $value || '' === $value) {
            return '';
        }

        return sprintf('%s@%s', $value, $this->domain);
    }
}
