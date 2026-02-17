<?php

namespace App\Entity;

use App\Repository\MenuRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
#[ORM\Table(name: 'menu')]
#[ORM\HasLifecycleCallbacks]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    private string $name = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000)]
    private ?string $description = null;

    // DB: base_price
    #[ORM\Column(name: 'base_price', type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private string $price = '0.00';

    // DB: stock real
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    private int $stock = 0;

    // stock reservado em carrinhos
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    private int $reserved = 0;

    // imagem principal
    #[ORM\Column(name: 'image_url', length: 255, nullable: true)]
    private ?string $imageUrl = null;

    /**
     * 🆕 opcional: duas imagens extra para o modal (miniaturas)
     * Guarda como JSON array de strings: ["url1","url2"]
     */
    #[ORM\Column(name: 'extra_images', type: Types::JSON, nullable: true)]
    private ?array $extraImages = null;

    // 🆕 Ingredientes (texto)
    #[ORM\Column(name: 'ingredients', type: Types::TEXT, nullable: true)]
    private ?string $ingredients = null;

    // 🆕 Alergénios (texto)
    #[ORM\Column(name: 'allergens', type: Types::TEXT, nullable: true)]
    private ?string $allergens = null;

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (!isset($this->createdAt)) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self
    {
        $this->name = trim($name);
        return $this;
    }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self
    {
        $this->description = $description !== null ? trim($description) : null;
        return $this;
    }

    public function getPrice(): string { return $this->price; }
    public function setPrice(string $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getStock(): int { return $this->stock; }
    public function setStock(int $stock): self
    {
        $this->stock = max(0, $stock);
        return $this;
    }

    public function getReserved(): int { return $this->reserved; }
    public function setReserved(int $reserved): self
    {
        $this->reserved = max(0, $reserved);
        return $this;
    }

    public function getAvailableStock(): int
    {
        return max(0, $this->stock - $this->reserved);
    }

    public function isSoldOut(): bool
    {
        return $this->getAvailableStock() <= 0;
    }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl !== null ? trim($imageUrl) : null;
        return $this;
    }

    // 🆕 2 imagens extra
    public function getExtraImages(): array
    {
        return is_array($this->extraImages) ? $this->extraImages : [];
    }

    public function setExtraImages(?array $extraImages): self
    {
        // normaliza para array de strings
        if ($extraImages === null) {
            $this->extraImages = null;
            return $this;
        }

        $clean = [];
        foreach ($extraImages as $u) {
            $u = trim((string)$u);
            if ($u !== '') $clean[] = $u;
        }
        $this->extraImages = $clean ?: null;

        return $this;
    }

    // conveniência: todas as imagens (main + extras)
    public function getImages(): array
    {
        $images = [];
        if ($this->imageUrl) $images[] = $this->imageUrl;
        foreach ($this->getExtraImages() as $u) $images[] = $u;
        return $images;
    }

    public function getIngredients(): ?string { return $this->ingredients; }
    public function setIngredients(?string $ingredients): self
    {
        $this->ingredients = $ingredients !== null ? trim($ingredients) : null;
        return $this;
    }

    public function getAllergens(): ?string { return $this->allergens; }
    public function setAllergens(?string $allergens): self
    {
        $this->allergens = $allergens !== null ? trim($allergens) : null;
        return $this;
    }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
