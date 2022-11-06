<?php

namespace App\Security\Encoder;

use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class LegacyPasswordHasher implements PasswordHasherInterface
{
    use CheckPasswordLengthTrait;

    private string $algorithm;
    private bool $encodeHashAsBase64;
    private int $iterations;

    /**
     * Constructor.
     *
     * @param string $algorithm          The digest algorithm to use
     * @param bool   $encodeHashAsBase64 Whether to base64 encode the password hash
     * @param int    $iterations         The number of iterations to use to stretch the password hash
     */
    public function __construct(string $algorithm = 'sha512', bool $encodeHashAsBase64 = false, int $iterations = 5000)
    {
        $this->algorithm = $algorithm;
        $this->encodeHashAsBase64 = $encodeHashAsBase64;
        $this->iterations = $iterations;
    }

    /**
     * @param string $hashedPassword
     *
     * @return bool
     */
    public function needsRehash(string $hashedPassword): bool {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function hash(string $plainPassword): string
    {
        if ($this->isPasswordTooLong($plainPassword)) {
            throw new InvalidPasswordException('Invalid password.');
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

        $salt = uniqid(mt_rand(), true);

        $digest = crypt($plainPassword, sprintf('$%d$rounds=%d$%s$', $hashId, $this->iterations, $salt));

        return $this->encodeHashAsBase64 ? base64_encode($digest) : $digest;
    }

    /**
     * {@inheritdoc}
     */
    public function verify(string $hashedPassword, $plainPassword): bool
    {
        if ('' === $plainPassword || $this->isPasswordTooLong($plainPassword)) {
            return false;
        }

        return hash_equals(crypt($plainPassword, $hashedPassword), $hashedPassword);
    }
}
