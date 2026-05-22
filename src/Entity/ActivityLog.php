<?php

namespace App\Entity;

use App\Repository\ActivityLogRepository;
use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete()
    ]
)]

#[ORM\Entity(repositoryClass: ActivityLogRepository::class)]
#[ORM\Table(name: "activity_logs")]
#[ORM\Index(columns: ["user_id"], name: "idx_user_id")]
#[ORM\Index(columns: ["action"], name: "idx_action")]
#[ORM\Index(columns: ["created_at"], name: "idx_created_at")]
class ActivityLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $user = null;

    #[ORM\Column(type: "string", length: 50)]
    private ?string $action = null; // CREATE, UPDATE, DELETE, LOGIN, LOGOUT

    #[ORM\Column(type: "string", length: 100)]
    private ?string $entityType = null; // Order, Product, Service, User, etc.

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $entityId = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $affectedData = null; // JSON string of changed data

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "string", length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): self
    {
        $this->entityType = $entityType;
        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): self
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getAffectedData(): ?string
    {
        return $this->affectedData;
    }

    public function setAffectedData(?string $affectedData): self
    {
        $this->affectedData = $affectedData;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}

