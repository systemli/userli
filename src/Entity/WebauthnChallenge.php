<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WebauthnChallengeRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * A WebAuthn challenge issued for a relying party without own WebAuthn support (e.g. Roundcube).
 *
 * Kept in the database, so it can be used up atomically, see WebauthnChallengeRepository::consume().
 * Only a hash of the challenge is stored.
 */
#[ORM\Entity(repositoryClass: WebauthnChallengeRepository::class)]
#[ORM\Table(name: 'webauthn_challenges')]
#[ORM\UniqueConstraint(name: 'UNIQ_WEBAUTHN_CHALLENGE_HASH', columns: ['challenge_hash'])]
#[ORM\Index(name: 'IDX_WEBAUTHN_CHALLENGE_EXPIRES_AT', columns: ['expires_at'])]
class WebauthnChallenge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 64)]
    private string $challengeHash;

    #[ORM\Column]
    private DateTimeImmutable $expiresAt;

    public function __construct(User $user, string $challenge, DateTimeImmutable $expiresAt)
    {
        $this->user = $user;
        $this->challengeHash = self::hashChallenge($challenge);
        $this->expiresAt = $expiresAt;
    }

    public static function hashChallenge(string $challenge): string
    {
        return hash('sha256', $challenge);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getChallengeHash(): string
    {
        return $this->challengeHash;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
