<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MailAppPasswordRepository;
use App\Traits\CreationTimeTrait;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MailAppPasswordRepository::class)]
#[ORM\Table(name: 'mail_app_passwords')]
#[ORM\UniqueConstraint(name: 'UNIQ_MAIL_APP_PASSWORD_USER_NAME', columns: ['user_id', 'name'])]
class MailAppPassword
{
    use CreationTimeTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 64)]
    private string $name;

    #[ORM\Column(length: 64, unique: true)]
    private string $hash;

    #[ORM\Column(type: 'text')]
    private string $mailCryptSecretBox;

    public function __construct(User $user, string $name, string $hash, string $mailCryptSecretBox)
    {
        $this->user = $user;
        $this->name = $name;
        $this->hash = $hash;
        $this->mailCryptSecretBox = $mailCryptSecretBox;
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getMailCryptSecretBox(): string
    {
        return $this->mailCryptSecretBox;
    }
}
