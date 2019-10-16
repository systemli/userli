<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class OptionalDomainEmailTransformer implements DataTransformerInterface
{
    /**
     * @var string
     */
    private $domain;

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
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        if (false !== $atPosition = stripos($value, '@')) {
            // cut of domain part
            return substr($value, 0, $atPosition);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (null === $value || '' === $value) {
            return '';
        }

        if (false !== stripos($value, '@')) {
            return $value;
        }

        // append primary domain
        return sprintf('%s@%s', $value, $this->domain);
    }
}
