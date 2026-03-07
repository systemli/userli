<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SettingRepository;
use App\Traits\UpdatedTimeTrait;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Persisted key-value pair for global application settings.
 *
 * Available setting names, types, and defaults are defined in config/settings.yaml.
 */
#[ORM\Entity(repositoryClass: SettingRepository::class)]
#[ORM\Table(name: 'settings')]
#[ORM\UniqueConstraint(name: 'UNIQ_SETTING_NAME', columns: ['name'])]
class Setting implements UpdatedTimeInterface
{
    use UpdatedTimeTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Setting key matching an entry in config/settings.yaml (e.g. "app_name", "smtp_quota_limit_per_hour"). */
    #[ORM\Column(length: 255)]
    private string $name;

    /** Serialized setting value. Type depends on the setting definition in config/settings.yaml. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private string $value;

    #[ORM\Column]
    private DateTimeImmutable $creationTime;

    public function __construct(string $name, string $value)
    {
        $this->name = $name;
        $this->value = $value;
        $this->creationTime = new DateTimeImmutable();
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

    public function getCreationTime(): DateTimeImmutable
    {
        return $this->creationTime;
    }
}
