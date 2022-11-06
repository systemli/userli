<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class OptionalDomainEmailTransformer implements DataTransformerInterface
{
    private string $domain;

    /**
     * Constructor.
     *
     * @param $domain
     */
    public function __construct($domain)
    {
        $this->domain = $domain;
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

        if (false !== strpos($value, '@')) {
            return $value;
        }

        // append primary domain
        return sprintf('%s@%s', $value, $this->domain);
    }
}
