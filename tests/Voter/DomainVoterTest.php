<?php

declare(strict_types=1);

namespace App\Tests\Voter;

use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use App\Enum\Roles;
use App\Service\DomainGuesser;
use App\Voter\DomainVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class DomainVoterTest extends TestCase
{
    private Domain $domainA;
    private Domain $domainB;
    private User $domainAdminA;

    protected function setUp(): void
    {
        $this->domainA = new Domain();
        $this->domainB = new Domain();

        $this->domainAdminA = new User('admin@domain-a.org');
        $this->domainAdminA->setDomain($this->domainA);
    }

    // --- supports() ---

    public function testSupportsUserWithValidAttribute(): void
    {
        $voter = $this->createVoter(isAdmin: true);

        foreach ([DomainVoter::VIEW, DomainVoter::EDIT, DomainVoter::DELETE] as $attribute) {
            $result = $voter->vote($this->createToken(), new User('test@example.org'), [$attribute]);
            self::assertNotSame(VoterInterface::ACCESS_ABSTAIN, $result, sprintf('Voter should not abstain for attribute "%s" on User', $attribute));
        }
    }

    public function testSupportsAliasWithValidAttribute(): void
    {
        $voter = $this->createVoter(isAdmin: true);

        foreach ([DomainVoter::VIEW, DomainVoter::EDIT, DomainVoter::DELETE] as $attribute) {
            $result = $voter->vote($this->createToken(), new Alias(), [$attribute]);
            self::assertNotSame(VoterInterface::ACCESS_ABSTAIN, $result, sprintf('Voter should not abstain for attribute "%s" on Alias', $attribute));
        }
    }

    public function testAbstainsOnUnsupportedAttribute(): void
    {
        $voter = $this->createVoter(isAdmin: true);

        $result = $voter->vote($this->createToken(), new User('test@example.org'), ['unsupported']);
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testAbstainsOnUnsupportedSubject(): void
    {
        $voter = $this->createVoter(isAdmin: true);

        $result = $voter->vote($this->createToken(), new Domain(), ['view']);
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    // --- Full admin ---

    public function testFullAdminCanDoEverything(): void
    {
        $voter = $this->createVoter(isAdmin: true);
        $adminUser = new User('superadmin@example.org');
        $adminUser->setRoles([Roles::ADMIN]);

        foreach ([DomainVoter::VIEW, DomainVoter::EDIT, DomainVoter::DELETE] as $attribute) {
            $result = $voter->vote($this->createToken(), $adminUser, [$attribute]);
            self::assertSame(VoterInterface::ACCESS_GRANTED, $result, sprintf('Full admin should be granted "%s" on admin user', $attribute));
        }
    }

    // --- Domain admin: user in same domain ---

    public function testDomainAdminCanViewUserInSameDomain(): void
    {
        $voter = $this->createVoter(isDomainAdmin: true);
        $user = new User('user@domain-a.org');
        $user->setDomain($this->domainA);

        $result = $voter->vote($this->createToken($this->domainAdminA), $user, [DomainVoter::VIEW]);
        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDomainAdminCanEditUserInSameDomain(): void
    {
        $voter = $this->createVoter(isDomainAdmin: true, guessedDomain: $this->domainA);
        $user = new User('user@domain-a.org');
        $user->setDomain($this->domainA);

        $result = $voter->vote($this->createToken($this->domainAdminA), $user, [DomainVoter::EDIT]);
        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDomainAdminCanDeleteUserInSameDomain(): void
    {
        $voter = $this->createVoter(isDomainAdmin: true);
        $user = new User('user@domain-a.org');
        $user->setDomain($this->domainA);

        $result = $voter->vote($this->createToken($this->domainAdminA), $user, [DomainVoter::DELETE]);
        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    // --- Domain admin: user in different domain ---

    public function testDomainAdminCannotViewUserInDifferentDomain(): void
    {
        $voter = $this->createVoter(isDomainAdmin: true);
        $user = new User('user@domain-b.org');
        $user->setDomain($this->domainB);

        $result = $voter->vote($this->createToken($this->domainAdminA), $user, [DomainVoter::VIEW]);
        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDomainAdminCannotEditUserInDifferentDomain(): void
    {
        $voter = $this->createVoter(isDomainAdmin: true, guessedDomain: $this->domainB);
        $user = new User('user@domain-b.org');
        $user->setDomain($this->domainB);

        $result = $voter->vote($this->createToken($this->domainAdminA), $user, [DomainVoter::EDIT]);
        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDomainAdminCannotDeleteUserInDifferentDomain(): void
    {
        $voter = $this->createVoter(isDomainAdmin: true);
        $user = new User('user@domain-b.org');
        $user->setDomain($this->domainB);

        $result = $voter->vote($this->createToken($this->domainAdminA), $user, [DomainVoter::DELETE]);
        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    // --- Domain admin: cannot manage admin users ---

    public function testDomainAdminCannotViewAdminUser(): void
    {
        $voter = $this->createVoter(isDomainAdmin: true);
        $adminUser = new User('admin@domain-a.org');
        $adminUser->setDomain($this->domainA);
        $adminUser->setRoles([Roles::ADMIN]);

        $result = $voter->vote($this->createToken($this->domainAdminA), $adminUser, [DomainVoter::VIEW]);
        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDomainAdminCannotEditAdminUser(): void
    {
        $voter = $this->createVoter(isDomainAdmin: true, guessedDomain: $this->domainA);
        $adminUser = new User('admin@domain-a.org');
        $adminUser->setDomain($this->domainA);
        $adminUser->setRoles([Roles::ADMIN]);

        $result = $voter->vote($this->createToken($this->domainAdminA), $adminUser, [DomainVoter::EDIT]);
        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDomainAdminCannotDeleteAdminUser(): void
    {
        $voter = $this->createVoter(isDomainAdmin: true);
        $adminUser = new User('admin@domain-a.org');
        $adminUser->setDomain($this->domainA);
        $adminUser->setRoles([Roles::ADMIN]);

        $result = $voter->vote($this->createToken($this->domainAdminA), $adminUser, [DomainVoter::DELETE]);
        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    // --- Domain admin: alias operations ---

    public function testDomainAdminCanViewAliasInSameDomain(): void
    {
        $voter = $this->createVoter(isDomainAdmin: true);
        $alias = new Alias();
        $alias->setDomain($this->domainA);

        $result = $voter->vote($this->createToken($this->domainAdminA), $alias, [DomainVoter::VIEW]);
        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDomainAdminCanEditAliasInSameDomain(): void
    {
        $voter = $this->createVoter(isDomainAdmin: true, guessedDomain: $this->domainA);
        $alias = new Alias();
        $alias->setDomain($this->domainA);
        $alias->setSource('alias@domain-a.org');

        $result = $voter->vote($this->createToken($this->domainAdminA), $alias, [DomainVoter::EDIT]);
        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDomainAdminCannotViewAliasInDifferentDomain(): void
    {
        $voter = $this->createVoter(isDomainAdmin: true);
        $alias = new Alias();
        $alias->setDomain($this->domainB);

        $result = $voter->vote($this->createToken($this->domainAdminA), $alias, [DomainVoter::VIEW]);
        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDomainAdminCannotEditAliasInDifferentDomain(): void
    {
        $voter = $this->createVoter(isDomainAdmin: true, guessedDomain: $this->domainB);
        $alias = new Alias();
        $alias->setDomain($this->domainB);
        $alias->setSource('alias@domain-b.org');

        $result = $voter->vote($this->createToken($this->domainAdminA), $alias, [DomainVoter::EDIT]);
        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    // --- Regular user (neither admin nor domain admin) ---

    public function testRegularUserIsDenied(): void
    {
        $voter = $this->createVoter(isAdmin: false, isDomainAdmin: false);
        $user = new User('user@domain-a.org');
        $user->setDomain($this->domainA);

        $result = $voter->vote($this->createToken($this->domainAdminA), $user, [DomainVoter::VIEW]);
        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    // --- Helpers ---

    private function createVoter(bool $isAdmin = false, bool $isDomainAdmin = false, ?Domain $guessedDomain = null): DomainVoter
    {
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturnCallback(
            static function (string $role) use ($isAdmin, $isDomainAdmin): bool {
                return match ($role) {
                    Roles::ADMIN => $isAdmin,
                    Roles::DOMAIN_ADMIN => $isDomainAdmin || $isAdmin,
                    default => false,
                };
            }
        );

        $domainGuesser = $this->createStub(DomainGuesser::class);
        $domainGuesser->method('guess')->willReturn($guessedDomain);

        return new DomainVoter($security, $domainGuesser);
    }

    private function createToken(?User $user = null): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}
