<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Orders;
use App\Entity\Services;
use App\Entity\Products;

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

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[ORM\Table(name: "order_items")]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    // Relation to Orders
    #[ORM\ManyToOne(targetEntity: Orders::class, inversedBy: "orderItems")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Orders $order = null;

    // Type of the item: 'product' or 'service'
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $type = null;

    // Relation to Service (nullable)
    #[ORM\ManyToOne(targetEntity: Services::class, inversedBy: "orderItems")]
    #[ORM\JoinColumn(nullable: true)]
    private ?Services $service = null;

    // Relation to Product (nullable)
    #[ORM\ManyToOne(targetEntity: Products::class, inversedBy: "orderItems")]
    #[ORM\JoinColumn(nullable: true)]
    private ?Products $product = null;

    // Name at the time of order
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    // Price at the time of order
    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private ?string $price = null;

    // Quantity
    #[ORM\Column(type: "integer")]
    private int $quantity = 1;

    public function __construct()
    {
        $this->quantity = 1;
    }

    // ----------------------
    // Getters & Setters
    // ----------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Orders
    {
        return $this->order;
    }

    public function setOrder(?Orders $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getService(): ?Services
    {
        return $this->service;
    }

    public function setService(?Services $service): self
    {
        $this->service = $service;

        if ($service !== null) {
            $this->type = 'service';
            $this->product = null; // ensure product is null if service is set
        } elseif ($this->product === null) {
            $this->type = null; // clear type if no service or product
        }

        return $this;
    }

    public function getProduct(): ?Products
    {
        return $this->product;
    }

    public function setProduct(?Products $product): self
    {
        $this->product = $product;

        if ($product !== null) {
            $this->type = 'product';
            $this->service = null; // ensure service is null if product is set
        } elseif ($this->service === null) {
            $this->type = null; // clear type if no service or product
        }

        return $this;
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

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * Get subtotal for this order item
     */
    public function getSubtotal(): float
    {
        return (float)$this->price * $this->quantity;
    }
}
