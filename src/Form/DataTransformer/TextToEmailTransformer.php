<?php

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

    /**
     * {@inheritdoc}
     * @return false|string
     */
    public function transform($value): mixed
    {
        if (null === $value) {
            return '';
        }

        return substr((string) $value, 0, strpos((string) $value, '@'));
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value): string
    {
        if (null === $value || '' === $value) {
            return '';
        }

        return sprintf('%s@%s', $value, $this->domain);
    }
}
