<?php

namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\OrderItem;

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

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ORM\Table(name: 'services')]
class Services
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: "text")]
    private ?string $description = null;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\Column(type: "string", length: 100)]
    private ?string $category = null;

    #[ORM\Column(type: "boolean")]
    private bool $isActive = true;

    // ===== Relation to OrderItem =====
    #[ORM\OneToMany(mappedBy: "service", targetEntity: OrderItem::class, cascade: ["persist", "remove"])]
    private Collection $orderItems;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
    }

    // =====================
    // Getters & Setters
    // =====================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    // =====================
    // OrderItem relation methods
    // =====================

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $item): self
    {
        if (!$this->orderItems->contains($item)) {
            $this->orderItems->add($item);
            $item->setService($this); // sync service in OrderItem
            $item->setProduct(null);  // ensure product is null if service is set
            $item->setType('service'); // keep type in sync
        }
        return $this;
    }

    public function removeOrderItem(OrderItem $item): self
    {
        if ($this->orderItems->removeElement($item)) {
            if ($item->getService() === $this) {
                $item->setService(null);
                $item->setType(null); // clear type if removed
            }
        }
        return $this;
    }
}
