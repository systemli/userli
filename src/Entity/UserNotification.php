<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\UserNotificationType;
use App\Repository\UserNotificationRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Notification shown to a specific user (e.g. compromised password warning).
 *
 * Created programmatically when the system detects a condition the user should
 * be informed about. Notification types are defined in {@see UserNotificationType}.
 */
#[ORM\Entity(repositoryClass: UserNotificationRepository::class)]
#[ORM\Table(name: 'user_notifications')]
#[ORM\Index(columns: ['user_id', 'type', 'creation_time'], name: 'idx_user_type_creation_time')]
#[ORM\Index(columns: ['user_id', 'type'], name: 'idx_user_type')]
class UserNotification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /** Notification type stored as string (see {@see UserNotificationType} enum). */
    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    /** Optional JSON payload with notification-specific data. */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $creationTime;

    public function __construct(User $user, UserNotificationType $type, ?array $metadata = null)
    {
        $this->user = $user;
        $this->type = $type->value;
        $this->metadata = $metadata;
        $this->creationTime = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getType(): UserNotificationType
    {
        return UserNotificationType::from($this->type);
    }

    public function getCreationTime(): DateTimeImmutable
    {
        return $this->creationTime;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): void
    {
        $this->metadata = $metadata;
    }
}
