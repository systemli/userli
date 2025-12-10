<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements DataTransformerInterface<string, string>
 */
class TextToEmailTransformer implements DataTransformerInterface
{
    /**
     * Constructor.
     */
    public function __construct(private readonly string $domain)
    {
    }

    public function transform(mixed $value): mixed
    {
        if (null === $value) {
            return '';
        }

        $pos = strpos((string) $value, '@');

        return false === $pos ? (string) $value : substr((string) $value, 0, $pos);
    }

    public function reverseTransform(mixed $value): mixed
    {
        if (null === $value || '' === $value) {
            return '';
        }

        return sprintf('%s@%s', $value, $this->domain);
    }
}
