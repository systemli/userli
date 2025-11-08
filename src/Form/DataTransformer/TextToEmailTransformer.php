<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

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

        return substr((string) $value, 0, strpos((string) $value, '@'));
    }

    public function reverseTransform(mixed $value): mixed
    {
        if (null === $value || '' === $value) {
            return '';
        }

        return sprintf('%s@%s', $value, $this->domain);
    }
}
