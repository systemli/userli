<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\HttpFoundation\RateLimiter\AbstractRequestRateLimiter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Limits password and two-factor code checks per account, not per client IP: the proxy may anonymize
 * client IPs, and onion clients share one address.
 */
final class LoginRateLimiter extends AbstractRequestRateLimiter
{
    public function __construct(
        private readonly RateLimiterFactoryInterface $loginPasswordLimiter,
        private readonly RateLimiterFactoryInterface $loginTwofactorLimiter,
        private readonly EntityManagerInterface $manager,
    ) {
    }

    public function createPasswordLimiter(string $username): LimiterInterface
    {
        return $this->loginPasswordLimiter->create($this->normalize($username));
    }

    public function createTwofactorLimiter(string $username): LimiterInterface
    {
        return $this->loginTwofactorLimiter->create($this->normalize($username));
    }

    #[Override]
    protected function getLimiters(Request $request): array
    {
        $username = (string) $request->attributes->get(SecurityRequestAttributes::LAST_USERNAME, '');

        return ['2fa_login_check' === $request->attributes->get('_route')
            ? $this->createTwofactorLimiter($username)
            : $this->createPasswordLimiter($username), ];
    }

    /**
     * Same address as the UserProvider resolves, so "user" and "User@example.org" share one limit.
     */
    private function normalize(string $username): string
    {
        $username = mb_strtolower(trim($username));
        if (!str_contains($username, '@')) {
            $username .= '@'.(string) $this->manager->getRepository(Domain::class)->getDefaultDomain()?->getName();
        }

        return $username;
    }
}
