<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class TextToEmailTransformer implements DataTransformerInterface
{
    /**
     * Constructor.
     */
    public function __construct(private string $domain)
    {
    }

    /**
     * {@inheritdoc}
     * @return false|string
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        return substr($value, 0, strpos($value, '@'));
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
