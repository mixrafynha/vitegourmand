<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\Index(columns: ['status'], name: 'idx_commande_status')]
class Commande
{
    public const STATUS_PENDING   = 'PENDING';    // criada, à espera (ex: pagamento/aceitação)
    public const STATUS_ACCEPTED  = 'ACCEPTED';   // paga/confirmada (ou aceite)
    public const STATUS_REFUSED   = 'REFUSED';
    public const STATUS_PREPARING = 'PREPARING';
    public const STATUS_READY     = 'READY';
    public const STATUS_DELIVERING= 'DELIVERING';
    public const STATUS_DELIVERED = 'DELIVERED';
    public const STATUS_CANCELLED = 'CANCELLED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deliveryAddress = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $deliveryFee = '0.00';

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private string $discountRate = '0.00'; // ex: 10.00

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $totalAmount = '0.00';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cancelReason = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    // ==========================
    // 🔒 Campos de pagamento (máxima segurança)
    // ==========================

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $paymentProvider = null; // ex: "stripe"

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $paymentSessionId = null; // Stripe Checkout session id

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $paymentIntentId = null; // Stripe payment_intent id

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $idempotencyKey = null; // evita duplicar checkout

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $paymentStatus = null; // ex: "paid", "failed", etc.

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    // ==========================
    // Items
    // ==========================
    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: CommandeItem::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $u): self { $this->user = $u; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $s): self { $this->status = $s; return $this; }

    public function getDeliveryAddress(): ?string { return $this->deliveryAddress; }
    public function setDeliveryAddress(?string $a): self { $this->deliveryAddress = $a; return $this; }

    public function getDeliveryFee(): string { return $this->deliveryFee; }
    public function setDeliveryFee(string $f): self { $this->deliveryFee = $f; return $this; }

    public function getDiscountRate(): string { return $this->discountRate; }
    public function setDiscountRate(string $r): self { $this->discountRate = $r; return $this; }

    public function getTotalAmount(): string { return $this->totalAmount; }
    public function setTotalAmount(string $t): self { $this->totalAmount = $t; return $this; }

    public function getCancelReason(): ?string { return $this->cancelReason; }
    public function setCancelReason(?string $cancelReason): self { $this->cancelReason = $cancelReason; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    // ✅ Payment getters/setters
    public function getPaymentProvider(): ?string { return $this->paymentProvider; }
    public function setPaymentProvider(?string $v): self { $this->paymentProvider = $v; return $this; }

    public function getPaymentSessionId(): ?string { return $this->paymentSessionId; }
    public function setPaymentSessionId(?string $v): self { $this->paymentSessionId = $v; return $this; }

    public function getPaymentIntentId(): ?string { return $this->paymentIntentId; }
    public function setPaymentIntentId(?string $v): self { $this->paymentIntentId = $v; return $this; }

    public function getIdempotencyKey(): ?string { return $this->idempotencyKey; }
    public function setIdempotencyKey(?string $v): self { $this->idempotencyKey = $v; return $this; }

    public function getPaymentStatus(): ?string { return $this->paymentStatus; }
    public function setPaymentStatus(?string $v): self { $this->paymentStatus = $v; return $this; }

    public function getPaidAt(): ?\DateTimeImmutable { return $this->paidAt; }
    public function setPaidAt(?\DateTimeImmutable $v): self { $this->paidAt = $v; return $this; }

    /** @return Collection<int, CommandeItem> */
    public function getItems(): Collection { return $this->items; }

    public function addItem(CommandeItem $item): self
    {
        if (!$this->items->contains($item)) {
            $item->setCommande($this);
            $this->items->add($item);
        }
        return $this;
    }

    public function removeItem(CommandeItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getCommande() === $this) {
                $item->setCommande(null);
            }
        }
        return $this;
    }

    // Helpers úteis
    public function isPaid(): bool
    {
        return $this->paidAt !== null || $this->paymentStatus === 'paid' || $this->status === self::STATUS_ACCEPTED;
    }
}
