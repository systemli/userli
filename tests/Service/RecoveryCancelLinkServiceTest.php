<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\RecoveryCancelLinkService;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RecoveryCancelLinkServiceTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User('user@example.org');
        $this->user->setRecoveryStartTime(new DateTimeImmutable('-1 hour'));
    }

    public function testGeneratePathIsSignedAndBoundToRecoveryProcess(): void
    {
        $path = $this->createService()->generatePath($this->user);

        self::assertStringStartsWith('/recovery/cancel?', $path);
        self::assertStringContainsString('email=user%40example.org', $path);
        self::assertStringContainsString('started='.$this->user->getRecoveryStartTime()?->getTimestamp(), $path);
        self::assertStringContainsString('_expiration=', $path);
        self::assertStringContainsString('_hash=', $path);
    }

    public function testGeneratePathThrowsWithoutRecoveryProcess(): void
    {
        $this->expectException(LogicException::class);
        $this->createService()->generatePath(new User('user@example.org'));
    }

    public function testResolveReturnsUserForValidLink(): void
    {
        $service = $this->createService();

        self::assertSame($this->user, $service->resolve(Request::create($service->generatePath($this->user))));
    }

    public function testResolveAcceptsPostToSignedUrl(): void
    {
        $service = $this->createService();
        $request = Request::create($service->generatePath($this->user), 'POST', ['_token' => 'csrf']);

        self::assertSame($this->user, $service->resolve($request));
    }

    public function testResolveRejectsTamperedLink(): void
    {
        $service = $this->createService();
        $path = str_replace('user%40example.org', 'other%40example.org', $service->generatePath($this->user));

        self::assertNull($service->resolve(Request::create($path)));
    }

    public function testResolveRejectsUnsignedLink(): void
    {
        self::assertNull($this->createService()->resolve(Request::create('/recovery/cancel?email=user%40example.org&started=1')));
    }

    public function testResolveRejectsLinkOfEarlierRecoveryProcess(): void
    {
        $service = $this->createService();
        $path = $service->generatePath($this->user);

        // Cancelled and started again: the old link must not cancel the new process
        $this->user->setRecoveryStartTime(new DateTimeImmutable());

        self::assertNull($service->resolve(Request::create($path)));
    }

    public function testResolveRejectsCancelledRecoveryProcess(): void
    {
        $service = $this->createService();
        $path = $service->generatePath($this->user);

        $this->user->eraseRecoveryStartTime();

        self::assertNull($service->resolve(Request::create($path)));
    }

    public function testResolveRejectsExpiredRecoveryProcess(): void
    {
        $this->user->setRecoveryStartTime(new DateTimeImmutable('-31 days'));
        $service = $this->createService();

        self::assertNull($service->resolve(Request::create($service->generatePath($this->user))));
    }

    public function testResolveRejectsUnknownUser(): void
    {
        $service = $this->createService(userExists: false);

        self::assertNull($service->resolve(Request::create($service->generatePath($this->user))));
    }

    private function createService(bool $userExists = true): RecoveryCancelLinkService
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')
            ->willReturnCallback(static fn (string $name, array $parameters) => '/recovery/cancel?'.http_build_query($parameters));

        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('findByEmail')->willReturnCallback(
            fn (string $email) => $userExists && 'user@example.org' === $email ? $this->user : null
        );

        return new RecoveryCancelLinkService(new UriSigner('secret'), $urlGenerator, $userRepository);
    }
}
