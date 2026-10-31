<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * WebAuthn assertion that Keycloak collected for a challenge it created. Binary values are base64url encoded.
 */
final class KeycloakWebauthnValidateDto
{
    private const string BASE64URL = '/^[A-Za-z0-9_-]+$/';

    #[Assert\NotBlank]
    #[Assert\Regex(self::BASE64URL)]
    private string $challenge = '';

    #[Assert\NotBlank]
    #[Assert\Regex(self::BASE64URL)]
    private string $credentialId = '';

    #[Assert\NotBlank]
    #[Assert\Regex(self::BASE64URL)]
    private string $clientDataJSON = '';

    #[Assert\NotBlank]
    #[Assert\Regex(self::BASE64URL)]
    private string $authenticatorData = '';

    #[Assert\NotBlank]
    #[Assert\Regex(self::BASE64URL)]
    private string $signature = '';

    #[Assert\Regex(self::BASE64URL)]
    private ?string $userHandle = null;

    public function getChallenge(): string
    {
        return $this->challenge;
    }

    public function setChallenge(string $challenge): void
    {
        $this->challenge = $challenge;
    }

    public function getCredentialId(): string
    {
        return $this->credentialId;
    }

    public function setCredentialId(string $credentialId): void
    {
        $this->credentialId = $credentialId;
    }

    public function getClientDataJSON(): string
    {
        return $this->clientDataJSON;
    }

    public function setClientDataJSON(string $clientDataJSON): void
    {
        $this->clientDataJSON = $clientDataJSON;
    }

    public function getAuthenticatorData(): string
    {
        return $this->authenticatorData;
    }

    public function setAuthenticatorData(string $authenticatorData): void
    {
        $this->authenticatorData = $authenticatorData;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function setSignature(string $signature): void
    {
        $this->signature = $signature;
    }

    public function getUserHandle(): ?string
    {
        return $this->userHandle;
    }

    public function setUserHandle(?string $userHandle): void
    {
        $this->userHandle = '' === $userHandle ? null : $userHandle;
    }
}
