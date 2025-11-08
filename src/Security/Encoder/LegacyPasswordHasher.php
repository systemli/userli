<?php

declare(strict_types=1);

namespace App\Security\Encoder;

use LogicException;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class LegacyPasswordHasher implements PasswordHasherInterface
{
    use CheckPasswordLengthTrait;

    /**
     * Constructor.
     *
     * @param string $algorithm          The digest algorithm to use
     * @param bool   $encodeHashAsBase64 Whether to base64 encode the password hash
     * @param int    $iterations         The number of iterations to use to stretch the password hash
     */
    public function __construct(private string $algorithm = 'sha512', private bool $encodeHashAsBase64 = false, private int $iterations = 5000)
    {
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return true;
    }

    public function hash(string $plainPassword): string
    {
        if ($this->isPasswordTooLong($plainPassword)) {
            throw new InvalidPasswordException('Invalid password.');
        }

        $hashId = match ($this->algorithm) {
            'sha256' => 5,
            'sha512' => 6,
            default => throw new LogicException(sprintf('The algorithm "%s" is not supported.', $this->algorithm)),
        };

        $salt = uniqid((string) mt_rand(), true);

        $digest = crypt($plainPassword, sprintf('$%d$rounds=%d$%s$', $hashId, $this->iterations, $salt));

        return $this->encodeHashAsBase64 ? base64_encode($digest) : $digest;
    }

    public function verify(string $hashedPassword, $plainPassword): bool
    {
        if ('' === $plainPassword || $this->isPasswordTooLong($plainPassword)) {
            return false;
        }

        return hash_equals(crypt((string) $plainPassword, $hashedPassword), $hashedPassword);
    }
}
