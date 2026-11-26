<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Override;
use Symfony\Component\HttpFoundation\RateLimiter\AbstractRequestRateLimiter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Limits two-factor code checks per account, not per client IP: the proxy may anonymize client IPs, and onion
 * clients share one address. Passwords aren't limited per account, as a stranger could then lock users out.
 */
final class TwofactorRateLimiter extends AbstractRequestRateLimiter
{
    public function __construct(private readonly RateLimiterFactoryInterface $twofactorLimiter)
    {
    }

    public function create(User $user): LimiterInterface
    {
        return $this->twofactorLimiter->create($user->getUserIdentifier());
    }

    #[Override]
    protected function getLimiters(Request $request): array
    {
        if ('2fa_login_check' !== $request->attributes->get('_route')) {
            return [];
        }

        // The identifier of the user who passed the password check
        return [$this->twofactorLimiter->create((string) $request->attributes->get(SecurityRequestAttributes::LAST_USERNAME, ''))];
    }
}
