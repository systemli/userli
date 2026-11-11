<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AnnouncementStatus;
use App\Repository\AnnouncementRepository;
use App\Traits\DomainAwareTrait;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Plain-text mail sent by an admin or domain admin to all active users of a domain.
 */
#[ORM\Entity(repositoryClass: AnnouncementRepository::class)]
#[ORM\Table(name: 'announcements')]
class Announcement
{
    use DomainAwareTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $subject;

    #[ORM\Column(type: 'text')]
    private string $body;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $author;

    #[ORM\Column(type: 'integer')]
    private int $recipientCount = 0;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status;

    /** Highest user ID whose mail is queued, to resume the fan-out after a failure. */
    #[ORM\Column(type: 'integer')]
    private int $lastQueuedUserId = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $creationTime;

    public function __construct(Domain $domain, string $subject, string $body, ?User $author, int $recipientCount)
    {
        $this->domain = $domain;
        $this->subject = $subject;
        $this->body = $body;
        $this->author = $author;
        $this->recipientCount = $recipientCount;
        $this->status = AnnouncementStatus::QUEUED->value;
        $this->creationTime = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function getRecipientCount(): int
    {
        return $this->recipientCount;
    }

    public function setRecipientCount(int $recipientCount): void
    {
        $this->recipientCount = $recipientCount;
    }

    public function getStatus(): AnnouncementStatus
    {
        return AnnouncementStatus::from($this->status);
    }

    public function setStatus(AnnouncementStatus $status): void
    {
        $this->status = $status->value;
    }

    public function getLastQueuedUserId(): int
    {
        return $this->lastQueuedUserId;
    }

    public function setLastQueuedUserId(int $lastQueuedUserId): void
    {
        $this->lastQueuedUserId = $lastQueuedUserId;
    }

    public function getCreationTime(): DateTimeImmutable
    {
        return $this->creationTime;
    }
}
