<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class OptionalDomainEmailTransformer implements DataTransformerInterface
{
    /**
     * Constructor.
     *
     * @param $domain
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

        if (false !== $atPosition = strpos($value, '@')) {
            // cut of domain part
            return substr($value, 0, $atPosition);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     * @return mixed
     */
    public function reverseTransform($value)
    {
        if (null === $value || '' === $value) {
            return '';
        }

        if (str_contains($value, '@')) {
            return $value;
        }

        // append primary domain
        return sprintf('%s@%s', $value, $this->domain);
    }
}
