<?php

namespace App\Entity;

use App\Repository\CustomerNotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerNotificationRepository::class)]
#[ORM\Table(name: 'customer_notifications')]
#[ORM\Index(columns: ['user_id'], name: 'idx_customer_notif_user')]
#[ORM\Index(columns: ['created_at'], name: 'idx_customer_notif_created')]
class CustomerNotification
{
    public const CATEGORY_ORDER = 'order';
    public const CATEGORY_PRODUCT = 'product';

    public const TYPE_INFO = 'info';
    public const TYPE_SUCCESS = 'success';
    public const TYPE_WARNING = 'warning';
    public const TYPE_DANGER = 'danger';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /** Null = all customers (e.g. catalog updates). */
    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Users $user = null;

    #[ORM\Column(type: 'string', length: 30)]
    private string $category = self::CATEGORY_ORDER;

    #[ORM\Column(type: 'string', length: 40)]
    private string $event = '';

    #[ORM\Column(type: 'string', length: 120)]
    private string $title = '';

    #[ORM\Column(type: 'text')]
    private string $message = '';

    #[ORM\Column(type: 'string', length: 20, options: ['default' => self::TYPE_INFO])]
    private string $type = self::TYPE_INFO;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $entityType = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $entityId = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function setEvent(string $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function setEntityType(?string $entityType): self
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
