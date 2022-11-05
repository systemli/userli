<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class TextToEmailTransformer implements DataTransformerInterface
{
    private string $domain;

    /**
     * Constructor.
     */
    public function __construct(string $domain)
    {
        $this->domain = $domain;
    }

    /**
     * {@inheritdoc}
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
    public function reverseTransform($value)
    {
        if (null === $value || '' === $value) {
            return '';
        }

        return sprintf('%s@%s', $value, $this->domain);
    }
}
