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
#[ORM\UniqueConstraint(name: 'UNIQ_ANNOUNCEMENT_SEND_TOKEN', columns: ['send_token'])]
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

    #[ORM\Column(type: 'integer')]
    private int $sentCount = 0;

    /** Recipients that were skipped (deleted, flagged as spam) or whose mail failed permanently. */
    #[ORM\Column(type: 'integer')]
    private int $notSentCount = 0;

    /** One-time token from the preview, so that a form submitted twice sends only one announcement. */
    #[ORM\Column(type: 'string', length: 64)]
    private string $sendToken;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $creationTime;

    public function __construct(Domain $domain, string $subject, string $body, ?User $author, int $recipientCount, string $sendToken)
    {
        $this->sendToken = $sendToken;
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

    public function getSentCount(): int
    {
        return $this->sentCount;
    }

    public function getNotSentCount(): int
    {
        return $this->notSentCount;
    }

    /**
     * Number of queued mails that are neither sent nor given up yet.
     */
    public function getPendingCount(): int
    {
        return max(0, $this->recipientCount - $this->sentCount - $this->notSentCount);
    }

    /**
     * All recipients are queued and every mail is either sent or given up.
     */
    public function isDelivered(): bool
    {
        return AnnouncementStatus::DONE === $this->getStatus() && 0 === $this->getPendingCount();
    }

    public function getSendToken(): string
    {
        return $this->sendToken;
    }

    public function getCreationTime(): DateTimeImmutable
    {
        return $this->creationTime;
    }
}
