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
    public function __construct(private readonly string $domain)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function transform(mixed $value): mixed
    {
        if (null === $value) {
            return '';
        }

        if (false !== $atPosition = strpos((string) $value, '@')) {
            // cut of domain part
            return substr((string) $value, 0, $atPosition);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform(mixed $value): mixed
    {
        if (null === $value || '' === $value) {
            return '';
        }

        if (str_contains((string) $value, '@')) {
            return $value;
        }

        // append primary domain
        return sprintf('%s@%s', $value, $this->domain);
    }
}
