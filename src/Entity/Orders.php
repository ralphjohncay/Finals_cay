<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\State\OrdersProcessor;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(processor: OrdersProcessor::class),
        new Put(),
        new Delete()
    ]
)]

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Orders
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // ✅ Customer linked to Users entity
    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $customer = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $orderDate = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $totalPrice = '0.00';

    #[ORM\Column(length: 50)]
    private ?string $status = 'pending_approval'; // pending_approval | pending | approved | completed | canceled

    // ✅ Linked to OrderItem, cascade persist + orphan removal
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $orderItems;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->orderDate = new \DateTime();
    }

    // -------------------------
    // Getters & Setters
    // -------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Users
    {
        return $this->customer;
    }

    public function setCustomer(?Users $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function getOrderDate(): ?\DateTimeInterface
    {
        return $this->orderDate;
    }

    public function setOrderDate(\DateTimeInterface $orderDate): self
    {
        $this->orderDate = $orderDate;
        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): self
    {
        $this->totalPrice = number_format((float)$totalPrice, 2, '.', '');
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

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
            $item->setOrder($this);
        }
        $this->recalculateTotal();
        return $this;
    }

    public function removeOrderItem(OrderItem $item): self
    {
        if ($this->orderItems->removeElement($item)) {
            if ($item->getOrder() === $this) {
                $item->setOrder(null);
            }
        }
        $this->recalculateTotal();
        return $this;
    }

    /**
     * ✅ Recalculate totalPrice based on order items
     */
    public function recalculateTotal(): self
    {
        $total = 0.0;
        foreach ($this->orderItems as $item) {
            $subtotal = (float)$item->getSubtotal();
            $total += $subtotal;
        }
        $this->totalPrice = number_format($total, 2, '.', '');
        return $this;
    }

    /**
     * ✅ Utility: Clear all order items (optional helper)
     */
    public function clearOrderItems(): self
    {
        foreach ($this->orderItems as $item) {
            $this->removeOrderItem($item);
        }
        return $this;
    }
}
