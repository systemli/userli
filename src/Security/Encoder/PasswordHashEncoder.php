<?php

namespace App\Security\Encoder;

use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class PasswordHashEncoder extends BasePasswordEncoder
{
    private $algorithm;
    private $encodeHashAsBase64;
    private $iterations;

    /**
     * Constructor.
     *
     * @param string $algorithm          The digest algorithm to use
     * @param bool   $encodeHashAsBase64 Whether to base64 encode the password hash
     * @param int    $iterations         The number of iterations to use to stretch the password hash
     */
    public function __construct($algorithm = 'sha512', $encodeHashAsBase64 = false, $iterations = 5000)
    {
        $this->algorithm = $algorithm;
        $this->encodeHashAsBase64 = $encodeHashAsBase64;
        $this->iterations = $iterations;
    }

    /**
     * {@inheritdoc}
     */
    public function encodePassword($raw, $salt)
    {
        if ($this->isPasswordTooLong($raw)) {
            throw new BadCredentialsException('Invalid password.');
        }

        switch ($this->algorithm) {
            case 'sha256':
                $hashId = 5;
                break;
            case 'sha512':
                $hashId = 6;
                break;
            default:
                throw new \LogicException(sprintf('The algorithm "%s" is not supported.', $this->algorithm));
        }

        if (empty($salt)) {
            $salt = uniqid(mt_rand(), true);
        }

        $digest = crypt($raw, sprintf('$%d$rounds=%d$%s$', $hashId, $this->iterations, $salt));

        return $this->encodeHashAsBase64 ? base64_encode($digest) : $digest;
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordValid($encoded, $raw, $salt)
    {
        return !$this->isPasswordTooLong($raw) && $this->comparePasswords(crypt($raw, $encoded), $encoded);
    }
}
