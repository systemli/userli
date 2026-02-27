<?php

declare(strict_types=1);

namespace App\Entity;

use App\Traits\CreationTimeTrait;
use App\Traits\UpdatedTimeTrait;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(columns: ['enabled'])]
#[ORM\Table(name: 'webhook_endpoints')]
class WebhookEndpoint implements UpdatedTimeInterface
{
    use CreationTimeTrait;
    use UpdatedTimeTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 2048)]
    private string $url;

    #[ORM\Column(length: 255)]
    private string $secret;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $events = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    /** @var Collection<int, Domain> */
    #[ORM\ManyToMany(targetEntity: Domain::class)]
    #[ORM\JoinTable(name: 'webhook_endpoint_domain')]
    private Collection $domains;

    public function __construct(string $url, string $secret)
    {
        $this->url = $url;
        $this->secret = $secret;
        $this->creationTime = new DateTimeImmutable();
        $this->domains = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    public function getEvents(): ?array
    {
        return $this->events;
    }

    public function setEvents(?array $events): void
    {
        $this->events = $events;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /** @return Collection<int, Domain> */
    public function getDomains(): Collection
    {
        return $this->domains;
    }

    public function addDomain(Domain $domain): void
    {
        if (!$this->domains->contains($domain)) {
            $this->domains->add($domain);
        }
    }

    public function removeDomain(Domain $domain): void
    {
        $this->domains->removeElement($domain);
    }
}
