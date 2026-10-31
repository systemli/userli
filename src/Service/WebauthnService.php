<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use App\Exception\WebauthnException;
use App\Repository\WebauthnCredentialRepository;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\EdDSA\Ed25519;
use Cose\Algorithm\Signature\RSA\RS256;
use Cose\Algorithms;
use Doctrine\ORM\EntityManagerInterface;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Throwable;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\EmptyTrustPath;

use const JSON_THROW_ON_ERROR;
use const PHP_URL_HOST;

/**
 * WebAuthn ceremonies for security keys used as second factor.
 *
 * Credentials are registered and verified here only, both for the Userli login
 * and for assertions forwarded by the Keycloak user storage provider.
 */
final readonly class WebauthnService
{
    private const string SESSION_REGISTRATION_OPTIONS = 'webauthn_registration_options';

    private const string SESSION_AUTHENTICATION_OPTIONS = 'webauthn_authentication_options';

    private const int TIMEOUT = 60000;

    // Several forms may be rendered before one is submitted, e.g. in other tabs or after a failed attempt
    private const int MAX_PENDING_OPTIONS = 5;

    private SerializerInterface $serializer;

    public function __construct(
        private SettingsService $settingsService,
        private WebauthnCredentialRepository $repository,
        private EntityManagerInterface $manager,
        private RequestStack $requestStack,
        private LoggerInterface $logger,
    ) {
        $this->serializer = new WebauthnSerializerFactory(
            new AttestationStatementSupportManager([new NoneAttestationStatementSupport()])
        )->create();
    }

    /**
     * Creates the options for navigator.credentials.create() and keeps them in the session.
     */
    public function createRegistrationOptions(User $user): string
    {
        $options = PublicKeyCredentialCreationOptions::create(
            rp: PublicKeyCredentialRpEntity::create((string) $this->settingsService->get('project_name'), $this->getRpId()),
            user: PublicKeyCredentialUserEntity::create($user->getEmail(), $this->getUserHandle($user), $user->getEmail()),
            challenge: random_bytes(32),
            pubKeyCredParams: [
                PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_ES256),
                PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_EDDSA),
                PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_RS256),
            ],
            authenticatorSelection: AuthenticatorSelectionCriteria::create(
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_DISCOURAGED,
            ),
            attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            excludeCredentials: $this->getDescriptors($user),
            timeout: self::TIMEOUT,
        );

        $json = $this->toJson($options);
        $this->storeOptions(self::SESSION_REGISTRATION_OPTIONS, $options->challenge, $json);

        return $json;
    }

    /**
     * Verifies the response of navigator.credentials.create() against the options in the session
     * and stores the new credential.
     *
     * @throws WebauthnException
     */
    public function verifyRegistration(User $user, string $credentialJson, string $name): WebauthnCredential
    {
        try {
            $publicKeyCredential = $this->serializer->deserialize($credentialJson, PublicKeyCredential::class, 'json');
        } catch (Throwable $throwable) {
            throw new WebauthnException('Invalid registration response.', 0, $throwable);
        }

        $response = $publicKeyCredential->response;
        if (!$response instanceof AuthenticatorAttestationResponse) {
            throw new WebauthnException('Invalid registration response.');
        }

        $optionsJson = $this->takeOptions(self::SESSION_REGISTRATION_OPTIONS, $response->clientDataJSON->challenge);
        if (null === $optionsJson) {
            throw new WebauthnException('No registration in progress.');
        }

        try {
            $options = $this->serializer->deserialize($optionsJson, PublicKeyCredentialCreationOptions::class, 'json');

            $record = AuthenticatorAttestationResponseValidator::create($this->createCeremonyStepManagerFactory()->creationCeremony())
                ->check($response, $options, $this->getRpId());
        } catch (WebauthnException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            // Usually a misconfigured RP ID or origin, see the settings webauthn_rp_id and webauthn_origins
            $this->logger->warning('WebAuthn registration failed: {message}', ['message' => $exception->getMessage(), 'rpId' => $this->getRpId(), 'origins' => $this->getAllowedOrigins()]);

            throw new WebauthnException('Invalid registration response.', 0, $exception);
        }

        if (null !== $this->repository->findOneByCredentialId(Base64UrlSafe::encodeUnpadded($record->publicKeyCredentialId))) {
            throw new WebauthnException('Credential is already registered.');
        }

        $credential = new WebauthnCredential(
            $user,
            $name,
            Base64UrlSafe::encodeUnpadded($record->publicKeyCredentialId),
            Base64UrlSafe::encodeUnpadded($record->credentialPublicKey),
            Base64UrlSafe::encodeUnpadded($record->userHandle),
            $record->counter,
            $record->transports,
            $record->aaguid->toRfc4122(),
            $record->attestationType,
            $record->backupEligible,
            $record->backupStatus,
            $record->uvInitialized,
        );
        $this->manager->persist($credential);
        $user->getWebauthnCredentials()->add($credential);
        $this->manager->flush();

        return $credential;
    }

    /**
     * Creates the options for navigator.credentials.get() and keeps them in the session.
     */
    public function createAuthenticationOptions(User $user): string
    {
        $options = $this->createRequestOptions($user, random_bytes(32));
        $json = $this->toJson($options);
        $this->storeOptions(self::SESSION_AUTHENTICATION_OPTIONS, $options->challenge, $json);

        return $json;
    }

    /**
     * Verifies the response of navigator.credentials.get() against the options in the session.
     * Each challenge can be used once; input that is no assertion, like a backup code, uses up none.
     */
    public function verifyAuthentication(User $user, string $credentialJson): bool
    {
        try {
            $publicKeyCredential = $this->serializer->deserialize($credentialJson, PublicKeyCredential::class, 'json');
        } catch (Throwable $throwable) {
            // Expected for backup codes, which are submitted in the same field
            $this->logger->info('WebAuthn assertion failed: unreadable response: {message}', ['message' => $throwable->getMessage()]);

            return false;
        }

        $response = $publicKeyCredential->response;
        if (!$response instanceof AuthenticatorAssertionResponse) {
            $this->logger->notice('WebAuthn assertion failed: no assertion response');

            return false;
        }

        $optionsJson = $this->takeOptions(self::SESSION_AUTHENTICATION_OPTIONS, $response->clientDataJSON->challenge);
        if (null === $optionsJson) {
            $this->logger->notice('WebAuthn assertion failed: unknown or already used challenge');

            return false;
        }

        try {
            $options = $this->serializer->deserialize($optionsJson, PublicKeyCredentialRequestOptions::class, 'json');
        } catch (Throwable) {
            return false;
        }

        return $this->verifyAssertion($user, $publicKeyCredential, $options);
    }

    /**
     * Verifies an assertion that a relying party with an allowed origin (e.g. Keycloak) collected
     * for a challenge it created. All binary values are base64url encoded.
     */
    public function verifyExternalAssertion(
        User $user,
        string $challenge,
        string $credentialId,
        string $clientDataJson,
        string $authenticatorData,
        string $signature,
        ?string $userHandle,
    ): bool {
        try {
            $publicKeyCredential = $this->serializer->deserialize(json_encode([
                'id' => $credentialId,
                'rawId' => $credentialId,
                'type' => PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                'response' => [
                    'clientDataJSON' => $clientDataJson,
                    'authenticatorData' => $authenticatorData,
                    'signature' => $signature,
                    'userHandle' => $userHandle,
                ],
            ], JSON_THROW_ON_ERROR), PublicKeyCredential::class, 'json');
            $options = $this->createRequestOptions($user, Base64UrlSafe::decode($challenge));
        } catch (Throwable) {
            return false;
        }

        return $this->verifyAssertion($user, $publicKeyCredential, $options);
    }

    public function getRpId(): string
    {
        $rpId = (string) $this->settingsService->get('webauthn_rp_id', '');
        if ('' !== $rpId) {
            return $rpId;
        }

        return (string) parse_url((string) $this->settingsService->get('app_url'), PHP_URL_HOST);
    }

    /**
     * @return string[]
     */
    public function getAllowedOrigins(): array
    {
        $origins = [(string) $this->settingsService->get('app_url')];
        $additional = (string) $this->settingsService->get('webauthn_origins', '');
        foreach (preg_split('/\R/', $additional) ?: [] as $origin) {
            $origins[] = trim($origin);
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (string $origin): string => rtrim($origin, '/'),
            $origins,
        ))));
    }

    private function verifyAssertion(User $user, PublicKeyCredential $publicKeyCredential, PublicKeyCredentialRequestOptions $options): bool
    {
        $response = $publicKeyCredential->response;
        if (!$response instanceof AuthenticatorAssertionResponse) {
            return false;
        }

        // Compared by id, the user of the security token may be another instance than the one Doctrine loads
        $credential = $this->repository->findOneByCredentialId(Base64UrlSafe::encodeUnpadded($publicKeyCredential->rawId));
        if (null === $credential || !$this->isSameUser($credential->getUser(), $user)) {
            $this->logger->notice('WebAuthn assertion failed: credential not registered for the user', ['found' => null !== $credential]);

            return false;
        }

        // The user is known from the first factor, so the user handle of the credential is the expected one
        try {
            $record = AuthenticatorAssertionResponseValidator::create($this->createCeremonyStepManagerFactory()->requestCeremony())
                ->check($this->toCredentialRecord($credential), $response, $options, $this->getRpId(), Base64UrlSafe::decode($credential->getUserHandle()));
        } catch (Throwable $throwable) {
            $this->logger->notice('WebAuthn assertion failed: {message}', ['message' => $throwable->getMessage(), 'credential' => $credential->getId()]);

            return false;
        }

        $credential->recordUsage($record->counter, $record->backupEligible, $record->backupStatus, $record->uvInitialized);
        $this->manager->flush();

        return true;
    }

    private function isSameUser(User $a, User $b): bool
    {
        return $a === $b || (null !== $a->getId() && $a->getId() === $b->getId());
    }

    /**
     * Keeps the options of the latest ceremonies in the session, by challenge.
     */
    private function storeOptions(string $key, string $challenge, string $json): void
    {
        $session = $this->requestStack->getSession();
        $pending = $session->get($key);
        $pending = is_array($pending) ? $pending : [];
        $pending[Base64UrlSafe::encodeUnpadded($challenge)] = $json;
        $session->set($key, array_slice($pending, -self::MAX_PENDING_OPTIONS, null, true));
    }

    /**
     * Removes and returns the options created with the challenge, if they are pending.
     */
    private function takeOptions(string $key, string $challenge): ?string
    {
        $session = $this->requestStack->getSession();
        $pending = $session->get($key);
        $pending = is_array($pending) ? $pending : [];

        $id = Base64UrlSafe::encodeUnpadded($challenge);
        $json = $pending[$id] ?? null;
        unset($pending[$id]);
        $session->set($key, $pending);

        return is_string($json) ? $json : null;
    }

    private function createRequestOptions(User $user, string $challenge): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            challenge: $challenge,
            rpId: $this->getRpId(),
            allowCredentials: $this->getDescriptors($user),
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_DISCOURAGED,
            timeout: self::TIMEOUT,
        );
    }

    private function createCeremonyStepManagerFactory(): CeremonyStepManagerFactory
    {
        $factory = new CeremonyStepManagerFactory();
        $factory->setAllowedOrigins($this->getAllowedOrigins());
        $factory->setAlgorithmManager(Manager::create()->add(ES256::create(), Ed25519::create(), RS256::create()));

        return $factory;
    }

    /**
     * @return PublicKeyCredentialDescriptor[]
     */
    private function getDescriptors(User $user): array
    {
        return array_map(
            static fn (WebauthnCredential $credential): PublicKeyCredentialDescriptor => PublicKeyCredentialDescriptor::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                Base64UrlSafe::decode($credential->getCredentialId()),
                $credential->getTransports(),
            ),
            $this->repository->findByUser($user),
        );
    }

    /**
     * All credentials of a user share one random user handle.
     */
    private function getUserHandle(User $user): string
    {
        $credentials = $this->repository->findByUser($user);
        if ([] !== $credentials) {
            return Base64UrlSafe::decode($credentials[0]->getUserHandle());
        }

        return random_bytes(32);
    }

    private function toCredentialRecord(WebauthnCredential $credential): CredentialRecord
    {
        return CredentialRecord::create(
            publicKeyCredentialId: Base64UrlSafe::decode($credential->getCredentialId()),
            type: PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            transports: $credential->getTransports(),
            attestationType: $credential->getAttestationType(),
            trustPath: EmptyTrustPath::create(),
            aaguid: Uuid::fromString($credential->getAaguid()),
            credentialPublicKey: Base64UrlSafe::decode($credential->getPublicKey()),
            userHandle: Base64UrlSafe::decode($credential->getUserHandle()),
            counter: $credential->getSignCount(),
            backupEligible: $credential->getBackupEligible(),
            backupStatus: $credential->getBackupStatus(),
            uvInitialized: $credential->getUvInitialized(),
        );
    }

    private function toJson(PublicKeyCredentialCreationOptions|PublicKeyCredentialRequestOptions $options): string
    {
        return $this->serializer->serialize($options, 'json', [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            JsonEncode::OPTIONS => JSON_THROW_ON_ERROR,
        ]);
    }
}
