<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Domain;
use Symfony\Component\Validator\Constraints as Assert;

final class AnnouncementModel
{
    #[Assert\NotNull]
    private ?Domain $domain = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $subject = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 20000)]
    private string $body = '';

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): void
    {
        $this->domain = $domain;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): void
    {
        $this->subject = trim((string) $subject);
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(?string $body): void
    {
        $this->body = (string) $body;
    }

    /**
     * Identifies the previewed content, so that only previewed content can be sent.
     */
    public function getFingerprint(): string
    {
        return hash('sha256', implode("\0", [(string) $this->domain?->getId(), $this->subject, $this->body]));
    }
}
