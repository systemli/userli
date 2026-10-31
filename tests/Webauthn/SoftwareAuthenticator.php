<?php

declare(strict_types=1);

namespace App\Tests\Webauthn;

use CBOR\ByteStringObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use OpenSSLAsymmetricKey;
use ParagonIE\ConstantTime\Base64UrlSafe;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const OPENSSL_ALGO_SHA256;
use const OPENSSL_KEYTYPE_EC;
use const STR_PAD_LEFT;

/**
 * Minimal ES256 software authenticator that answers WebAuthn ceremonies
 * like a security key with "none" attestation.
 */
final class SoftwareAuthenticator
{
    private const int FLAG_USER_PRESENT = 0x01;

    private const int FLAG_ATTESTED_CREDENTIAL_DATA = 0x40;

    public readonly string $credentialId;

    /** @var array<string, self> Authenticators shared between Behat contexts, by user */
    private static array $registry = [];

    private readonly OpenSSLAsymmetricKey $key;

    public function __construct(public int $signCount = 0)
    {
        $this->credentialId = random_bytes(16);
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        assert($key instanceof OpenSSLAsymmetricKey);
        $this->key = $key;
    }

    public static function forUser(string $email): self
    {
        return self::$registry[$email] ??= new self();
    }

    public static function reset(): void
    {
        self::$registry = [];
    }

    /**
     * Answers navigator.credentials.create() for the given options JSON.
     */
    public function create(string $optionsJson, string $origin): string
    {
        $options = json_decode($optionsJson, true, flags: JSON_THROW_ON_ERROR);
        $clientDataJson = $this->clientDataJson('webauthn.create', $options['challenge'], $origin);
        $authData = $this->authenticatorData($options['rp']['id'], self::FLAG_USER_PRESENT | self::FLAG_ATTESTED_CREDENTIAL_DATA)
            .str_repeat("\0", 16)
            .pack('n', strlen($this->credentialId))
            .$this->credentialId
            .$this->publicKey();

        $attestationObject = MapObject::create()
            ->add(TextStringObject::create('fmt'), TextStringObject::create('none'))
            ->add(TextStringObject::create('attStmt'), MapObject::create())
            ->add(TextStringObject::create('authData'), ByteStringObject::create($authData));

        return json_encode([
            'id' => Base64UrlSafe::encodeUnpadded($this->credentialId),
            'rawId' => Base64UrlSafe::encodeUnpadded($this->credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => Base64UrlSafe::encodeUnpadded($clientDataJson),
                'attestationObject' => Base64UrlSafe::encodeUnpadded((string) $attestationObject),
                'transports' => ['usb'],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Answers navigator.credentials.get() and returns the parts of the assertion, base64url encoded.
     *
     * @return array{credentialId: string, clientDataJSON: string, authenticatorData: string, signature: string}
     */
    public function assert(string $rpId, string $challenge, string $origin): array
    {
        ++$this->signCount;
        $clientDataJson = $this->clientDataJson('webauthn.get', $challenge, $origin);
        $authData = $this->authenticatorData($rpId, self::FLAG_USER_PRESENT);
        openssl_sign($authData.hash('sha256', $clientDataJson, true), $signature, $this->key, OPENSSL_ALGO_SHA256);

        return [
            'credentialId' => Base64UrlSafe::encodeUnpadded($this->credentialId),
            'clientDataJSON' => Base64UrlSafe::encodeUnpadded($clientDataJson),
            'authenticatorData' => Base64UrlSafe::encodeUnpadded($authData),
            'signature' => Base64UrlSafe::encodeUnpadded($signature),
        ];
    }

    /**
     * Answers navigator.credentials.get() for the given options JSON, as the browser would serialize it.
     */
    public function get(string $optionsJson, string $origin): string
    {
        $options = json_decode($optionsJson, true, flags: JSON_THROW_ON_ERROR);
        $assertion = $this->assert($options['rpId'], $options['challenge'], $origin);

        return json_encode([
            'id' => $assertion['credentialId'],
            'rawId' => $assertion['credentialId'],
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => $assertion['clientDataJSON'],
                'authenticatorData' => $assertion['authenticatorData'],
                'signature' => $assertion['signature'],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function clientDataJson(string $type, string $challenge, string $origin): string
    {
        return json_encode([
            'type' => $type,
            'challenge' => $challenge,
            'origin' => $origin,
            'crossOrigin' => false,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    private function authenticatorData(string $rpId, int $flags): string
    {
        return hash('sha256', $rpId, true).chr($flags).pack('N', $this->signCount);
    }

    /**
     * COSE encoded public key, as stored for the credential.
     */
    public function publicKey(): string
    {
        $details = openssl_pkey_get_details($this->key);
        assert(is_array($details));

        return (string) MapObject::create()
            ->add(UnsignedIntegerObject::create(1), UnsignedIntegerObject::create(2))
            ->add(UnsignedIntegerObject::create(3), NegativeIntegerObject::create(-7))
            ->add(NegativeIntegerObject::create(-1), UnsignedIntegerObject::create(1))
            ->add(NegativeIntegerObject::create(-2), ByteStringObject::create(str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT)))
            ->add(NegativeIntegerObject::create(-3), ByteStringObject::create(str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT)));
    }
}
