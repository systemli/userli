<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\UserNotificationType;
use App\Repository\UserNotificationRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

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

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $creationTime;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata;

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
