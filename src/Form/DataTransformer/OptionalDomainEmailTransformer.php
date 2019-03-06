<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * @author tim <tim@systemli.org>
 */
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

        if (false !== $at_position = stripos($value, '@')) {
            // cut of domain part
            return substr($value, 0, $at_position);
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

        if (false !== $at_position = stripos($value, '@')) {
            return $value;
        }

        // append primary domain
        return sprintf('%s@%s', $value, $this->domain);
    }
}
