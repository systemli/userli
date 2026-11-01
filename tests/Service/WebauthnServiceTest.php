<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\WebauthnChallenge;
use App\Entity\WebauthnCredential;
use App\Exception\WebauthnException;
use App\Repository\WebauthnChallengeRepository;
use App\Repository\WebauthnCredentialRepository;
use App\Service\SettingsService;
use App\Service\WebauthnService;
use App\Tests\Webauthn\SoftwareAuthenticator;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use ParagonIE\ConstantTime\Base64UrlSafe;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class WebauthnServiceTest extends TestCase
{
    private const string ORIGIN = 'https://users.example.org';

    private const string KEYCLOAK_ORIGIN = 'https://login.example.org';

    /** @var WebauthnCredential[] */
    private array $credentials = [];

    /** @var WebauthnChallenge[] */
    private array $challenges = [];

    private Session $session;

    private WebauthnService $service;

    private User $user;

    protected function setUp(): void
    {
        $settings = $this->createStub(SettingsService::class);
        $settings->method('get')->willReturnMap([
            ['project_name', null, 'example.org'],
            ['app_url', null, self::ORIGIN],
            ['webauthn_rp_id', '', 'example.org'],
            ['webauthn_origins', '', self::KEYCLOAK_ORIGIN."\n"],
        ]);

        $repository = $this->createStub(WebauthnCredentialRepository::class);
        $repository->method('findByUser')->willReturnCallback(
            fn (User $user): array => array_values(array_filter($this->credentials, static fn (WebauthnCredential $c): bool => $c->getUser() === $user))
        );
        $repository->method('findOneByCredentialId')->willReturnCallback(function (string $id): ?WebauthnCredential {
            foreach ($this->credentials as $credential) {
                if ($credential->getCredentialId() === $id) {
                    return $credential;
                }
            }

            return null;
        });

        // Like the DELETE of the repository: only a pending, unexpired challenge of the user is used up
        $challengeRepository = $this->createStub(WebauthnChallengeRepository::class);
        $challengeRepository->method('consume')->willReturnCallback(function (User $user, string $challenge): bool {
            foreach ($this->challenges as $key => $pending) {
                if ($pending->getChallengeHash() === WebauthnChallenge::hashChallenge($challenge) && $pending->getUser() === $user && $pending->getExpiresAt() > new DateTimeImmutable()) {
                    unset($this->challenges[$key]);

                    return true;
                }
            }

            return false;
        });

        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('persist')->willReturnCallback(function (object $entity): void {
            if ($entity instanceof WebauthnCredential) {
                $this->credentials[] = $entity;
            } elseif ($entity instanceof WebauthnChallenge) {
                $this->challenges[] = $entity;
            }
        });

        $this->session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($this->session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $this->service = new WebauthnService($settings, $repository, $challengeRepository, $manager, $requestStack, new NullLogger());
        $this->user = new User('user@example.org');
    }

    public function testRegisterAndAuthenticate(): void
    {
        $authenticator = new SoftwareAuthenticator();
        $credential = $this->register($authenticator);

        self::assertSame('My key', $credential->getName());
        self::assertSame(['usb'], $credential->getTransports());
        self::assertTrue($this->user->isWebauthnAuthenticationEnabled());
        self::assertTrue($this->user->isTwofactorEnabled());

        $options = $this->service->createAuthenticationOptions($this->user);
        self::assertTrue($this->service->verifyAuthentication($this->user, $authenticator->get($options, self::ORIGIN)));
        self::assertSame(1, $credential->getSignCount());
        self::assertNotNull($credential->getLastUsedTime());
    }

    public function testRegistrationOptionsExcludeExistingCredentials(): void
    {
        $authenticator = new SoftwareAuthenticator();
        $this->register($authenticator);

        $options = json_decode($this->service->createRegistrationOptions($this->user), true);

        self::assertSame('example.org', $options['rp']['id']);
        self::assertSame('none', $options['attestation']);
        self::assertSame(Base64UrlSafe::encodeUnpadded($authenticator->credentialId), $options['excludeCredentials'][0]['id']);
    }

    public function testRegistrationsStartedInParallelShareTheUserHandle(): void
    {
        $first = json_decode($this->service->createRegistrationOptions($this->user), true);
        $second = json_decode($this->service->createRegistrationOptions($this->user), true);

        self::assertSame($first['user']['id'], $second['user']['id']);
        self::assertSame($first['user']['id'], $this->user->getWebauthnUserHandle());
    }

    public function testUserHandleOfExistingCredentialIsKept(): void
    {
        $credential = $this->register(new SoftwareAuthenticator());
        $this->user->setWebauthnUserHandle(null);

        $options = json_decode($this->service->createRegistrationOptions($this->user), true);

        self::assertSame($credential->getUserHandle(), $options['user']['id']);
    }

    public function testRegistrationFailsWithoutOptionsInSession(): void
    {
        $this->expectException(WebauthnException::class);

        $this->service->verifyRegistration($this->user, '{}', 'My key');
    }

    public function testRegistrationFailsForForeignOrigin(): void
    {
        $options = $this->service->createRegistrationOptions($this->user);

        $this->expectException(WebauthnException::class);

        $this->service->verifyRegistration($this->user, new SoftwareAuthenticator()->create($options, 'https://evil.example.com'), 'My key');
    }

    public function testAuthenticationOptionsAreSingleUse(): void
    {
        $authenticator = new SoftwareAuthenticator();
        $this->register($authenticator);

        $options = $this->service->createAuthenticationOptions($this->user);
        $response = $authenticator->get($options, self::ORIGIN);

        self::assertTrue($this->service->verifyAuthentication($this->user, $response));
        self::assertFalse($this->service->verifyAuthentication($this->user, $response));
    }

    public function testExpiredAuthenticationOptionsAreRefused(): void
    {
        $authenticator = new SoftwareAuthenticator();
        $this->register($authenticator);

        $options = $this->service->createAuthenticationOptions($this->user);
        $this->expireSessionOptions('webauthn_authentication_options');

        self::assertFalse($this->service->verifyAuthentication($this->user, $authenticator->get($options, self::ORIGIN)));
    }

    public function testExpiredRegistrationOptionsAreRefused(): void
    {
        $options = $this->service->createRegistrationOptions($this->user);
        $this->expireSessionOptions('webauthn_registration_options');

        $this->expectException(WebauthnException::class);

        $this->service->verifyRegistration($this->user, new SoftwareAuthenticator()->create($options, self::ORIGIN), 'My key');
    }

    public function testAuthenticationWithOlderOfSeveralRenderedForms(): void
    {
        $authenticator = new SoftwareAuthenticator();
        $this->register($authenticator);

        $first = $this->service->createAuthenticationOptions($this->user);
        $second = $this->service->createAuthenticationOptions($this->user);

        self::assertTrue($this->service->verifyAuthentication($this->user, $authenticator->get($first, self::ORIGIN)));
        self::assertTrue($this->service->verifyAuthentication($this->user, $authenticator->get($second, self::ORIGIN)));
    }

    public function testInvalidInputDoesNotUseUpChallenge(): void
    {
        $authenticator = new SoftwareAuthenticator();
        $this->register($authenticator);

        $options = $this->service->createAuthenticationOptions($this->user);

        // e.g. a backup code submitted with the same form
        self::assertFalse($this->service->verifyAuthentication($this->user, '123456'));
        self::assertTrue($this->service->verifyAuthentication($this->user, $authenticator->get($options, self::ORIGIN)));
    }

    public function testOnlyLatestChallengesArePending(): void
    {
        $authenticator = new SoftwareAuthenticator();
        $this->register($authenticator);

        $oldest = $this->service->createAuthenticationOptions($this->user);
        for ($i = 0; $i < 5; ++$i) {
            $this->service->createAuthenticationOptions($this->user);
        }

        self::assertFalse($this->service->verifyAuthentication($this->user, $authenticator->get($oldest, self::ORIGIN)));
    }

    public function testAuthenticationFailsForCredentialOfOtherUser(): void
    {
        $authenticator = new SoftwareAuthenticator();
        $this->register($authenticator);

        $otherUser = new User('other@example.org');
        $options = $this->service->createAuthenticationOptions($otherUser);

        self::assertFalse($this->service->verifyAuthentication($otherUser, $authenticator->get($options, self::ORIGIN)));
    }

    public function testAuthenticationFailsForUnknownCredential(): void
    {
        $this->register(new SoftwareAuthenticator());

        $options = $this->service->createAuthenticationOptions($this->user);

        self::assertFalse($this->service->verifyAuthentication($this->user, new SoftwareAuthenticator()->get($options, self::ORIGIN)));
    }

    public function testExternalAssertion(): void
    {
        $authenticator = new SoftwareAuthenticator();
        $credential = $this->register($authenticator);
        $challenge = $this->service->createExternalChallenge($this->user);

        $assertion = $authenticator->assert('example.org', $challenge, self::KEYCLOAK_ORIGIN);

        self::assertTrue($this->verifyExternal($challenge, $assertion));
        self::assertSame(1, $credential->getSignCount());
    }

    public function testExternalAssertionFailsForWrongChallenge(): void
    {
        $authenticator = new SoftwareAuthenticator();
        $this->register($authenticator);

        $assertion = $authenticator->assert('example.org', Base64UrlSafe::encodeUnpadded(random_bytes(32)), self::KEYCLOAK_ORIGIN);

        self::assertFalse($this->verifyExternal($this->service->createExternalChallenge($this->user), $assertion));
    }

    public function testExternalAuthenticationOptions(): void
    {
        $authenticator = new SoftwareAuthenticator();
        $this->register($authenticator);

        $options = $this->service->createExternalAuthenticationOptions($this->user);

        self::assertSame('example.org', $options['rpId']);
        self::assertSame(Base64UrlSafe::encodeUnpadded($authenticator->credentialId), $options['allowCredentials'][0]['id']);
        self::assertTrue($this->verifyExternal($options['challenge'], $authenticator->assert('example.org', $options['challenge'], self::KEYCLOAK_ORIGIN)));
    }

    public function testExternalAssertionFailsForChallengeNotIssued(): void
    {
        $authenticator = new SoftwareAuthenticator();
        $this->register($authenticator);
        $challenge = Base64UrlSafe::encodeUnpadded(random_bytes(32));

        self::assertFalse($this->verifyExternal($challenge, $authenticator->assert('example.org', $challenge, self::KEYCLOAK_ORIGIN)));
    }

    public function testExternalAssertionFailsForChallengeOfOtherUser(): void
    {
        $authenticator = new SoftwareAuthenticator();
        $this->register($authenticator);
        $challenge = $this->service->createExternalChallenge(new User('other@example.org'));

        self::assertFalse($this->verifyExternal($challenge, $authenticator->assert('example.org', $challenge, self::KEYCLOAK_ORIGIN)));
    }

    public function testExternalAssertionCannotBeReplayedWithoutSignCount(): void
    {
        $authenticator = new SoftwareAuthenticator();
        $this->register($authenticator);
        $challenge = $this->service->createExternalChallenge($this->user);

        // Like many passkeys, the authenticator always reports a sign count of 0
        $authenticator->signCount = -1;
        $assertion = $authenticator->assert('example.org', $challenge, self::KEYCLOAK_ORIGIN);

        self::assertTrue($this->verifyExternal($challenge, $assertion));
        self::assertFalse($this->verifyExternal($challenge, $assertion));
    }

    public function testExternalAssertionFailsForUnknownOrigin(): void
    {
        $authenticator = new SoftwareAuthenticator();
        $this->register($authenticator);
        $challenge = $this->service->createExternalChallenge($this->user);

        $assertion = $authenticator->assert('example.org', $challenge, 'https://evil.example.com');

        self::assertFalse($this->verifyExternal($challenge, $assertion));
    }

    public function testExternalAssertionFailsForWrongRpId(): void
    {
        $authenticator = new SoftwareAuthenticator();
        $this->register($authenticator);
        $challenge = $this->service->createExternalChallenge($this->user);

        $assertion = $authenticator->assert('evil.example.com', $challenge, self::KEYCLOAK_ORIGIN);

        self::assertFalse($this->verifyExternal($challenge, $assertion));
    }

    public function testExternalAssertionFailsForReplayedCounter(): void
    {
        $authenticator = new SoftwareAuthenticator();
        $this->register($authenticator);
        $challenge = $this->service->createExternalChallenge($this->user);
        self::assertTrue($this->verifyExternal($challenge, $authenticator->assert('example.org', $challenge, self::KEYCLOAK_ORIGIN)));

        // A cloned authenticator reuses an old counter value
        $authenticator->signCount = 0;
        $challenge = $this->service->createExternalChallenge($this->user);

        self::assertFalse($this->verifyExternal($challenge, $authenticator->assert('example.org', $challenge, self::KEYCLOAK_ORIGIN)));
    }

    public function testExternalAssertionFailsForInvalidSignature(): void
    {
        $authenticator = new SoftwareAuthenticator();
        $this->register($authenticator);
        $challenge = $this->service->createExternalChallenge($this->user);

        $assertion = $authenticator->assert('example.org', $challenge, self::KEYCLOAK_ORIGIN);
        $assertion['signature'] = new SoftwareAuthenticator()->assert('example.org', $challenge, self::KEYCLOAK_ORIGIN)['signature'];

        self::assertFalse($this->verifyExternal($challenge, $assertion));
    }

    public function testGetAllowedOrigins(): void
    {
        self::assertSame([self::ORIGIN, self::KEYCLOAK_ORIGIN], $this->service->getAllowedOrigins());
    }

    public function testRecordExternalUsage(): void
    {
        $credential = $this->register(new SoftwareAuthenticator());

        self::assertTrue($this->service->recordExternalUsage($credential, 5));
        self::assertSame(5, $credential->getSignCount());
        self::assertNotNull($credential->getLastUsedTime());
    }

    public function testRecordExternalUsageRefusesCountThatDoesNotIncrease(): void
    {
        $credential = $this->register(new SoftwareAuthenticator());
        self::assertTrue($this->service->recordExternalUsage($credential, 5));

        self::assertFalse($this->service->recordExternalUsage($credential, 5));
        self::assertFalse($this->service->recordExternalUsage($credential, 0));
        self::assertSame(5, $credential->getSignCount());
    }

    public function testRecordExternalUsageWithoutSignCount(): void
    {
        // Like many passkeys, the authenticator always reports a sign count of 0
        $credential = $this->register(new SoftwareAuthenticator());
        self::assertSame(0, $credential->getSignCount());

        self::assertTrue($this->service->recordExternalUsage($credential, 0));
        self::assertNotNull($credential->getLastUsedTime());
    }

    private function expireSessionOptions(string $key): void
    {
        $pending = $this->session->get($key);
        self::assertIsArray($pending);
        foreach (array_keys($pending) as $challenge) {
            $pending[$challenge]['expires'] = time() - 1;
        }
        $this->session->set($key, $pending);
    }

    private function register(SoftwareAuthenticator $authenticator): WebauthnCredential
    {
        $options = $this->service->createRegistrationOptions($this->user);

        return $this->service->verifyRegistration($this->user, $authenticator->create($options, self::ORIGIN), 'My key');
    }

    /**
     * @param array{credentialId: string, clientDataJSON: string, authenticatorData: string, signature: string} $assertion
     */
    private function verifyExternal(string $challenge, array $assertion): bool
    {
        return $this->service->verifyExternalAssertion(
            $this->user,
            $challenge,
            $assertion['credentialId'],
            $assertion['clientDataJSON'],
            $assertion['authenticatorData'],
            $assertion['signature'],
            null,
        );
    }
}
