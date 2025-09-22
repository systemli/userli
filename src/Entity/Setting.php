<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use App\Repository\SettingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SettingRepository::class)]
#[ORM\Table(name: 'settings')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'UNIQ_SETTING_NAME', columns: ['name'])]
class Setting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private string $value;

    #[ORM\Column]
    private DateTimeImmutable $creationTime;

    #[ORM\Column(nullable: true)]
    private DateTimeImmutable $updatedTime;

    public function __construct(string $name, string $value)
    {
        $this->name = $name;
        $this->value = $value;
        $this->creationTime = new DateTimeImmutable();
        $this->updatedTime = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    #[ORM\PreUpdate]
    public function setUpdatedTime(): void
    {
        $this->updatedTime = new DateTimeImmutable();
    }

    public function getCreationTime(): DateTimeImmutable
    {
        return $this->creationTime;
    }

    public function getUpdatedTime(): DateTimeImmutable
    {
        return $this->updatedTime;
    }
}
