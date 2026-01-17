<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\Table(name: 'books')]
#[ORM\HasLifecycleCallbacks]
class Book
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $price;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $coverImage;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $category;

    #[ORM\Column(type: 'uuid')]
    private Uuid $ownerUuid;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $companyUuid = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $ownerName;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'book', targetEntity: BookPurchase::class, cascade: ['persist', 'remove'])]
    private Collection $purchases;

    public function __construct(
        string $title,
        string $price,
        int $quantity,
        Uuid $ownerUuid,
        string $ownerName,
        ?string $description = null,
        ?string $coverImage = null,
        ?string $category = null
    ) {
        $this->id = Uuid::v4();
        $this->title = $title;
        $this->price = $price;
        $this->quantity = $quantity;
        $this->ownerUuid = $ownerUuid;
        $this->ownerName = $ownerName;
        $this->description = $description;
        $this->coverImage = $coverImage;
        $this->category = $category;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = null;
        $this->purchases = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): self
    {
        $this->coverImage = $coverImage;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getOwnerUuid(): Uuid
    {
        return $this->ownerUuid;
    }

    public function setOwnerUuid(Uuid $ownerUuid): self
    {
        $this->ownerUuid = $ownerUuid;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCompanyUuid(): ?Uuid
    {
        return $this->companyUuid;
    }

    public function setCompanyUuid(?Uuid $companyUuid): self
    {
        $this->companyUuid = $companyUuid;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getOwnerName(): string
    {
        return $this->ownerName;
    }

    public function setOwnerName(string $ownerName): self
    {
        $this->ownerName = $ownerName;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, BookPurchase>
     */
    public function getPurchases(): Collection
    {
        return $this->purchases;
    }

    public function addPurchase(BookPurchase $purchase): self
    {
        if (!$this->purchases->contains($purchase)) {
            $this->purchases->add($purchase);
            $purchase->setBook($this);
        }

        return $this;
    }

    public function removePurchase(BookPurchase $purchase): self
    {
        if ($this->purchases->removeElement($purchase)) {
            // set the owning side to null (unless already changed)
            if ($purchase->getBook() === $this) {
                $purchase->setBook(null);
            }
        }

        return $this;
    }

    /**
     * Decrease quantity when book is purchased
     */
    public function decreaseQuantity(int $amount): self
    {
        $this->quantity = max(0, $this->quantity - $amount);
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Increase quantity when book is returned/added
     */
    public function increaseQuantity(int $amount): self
    {
        $this->quantity += $amount;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Check if book is available for purchase
     */
    public function isAvailable(): bool
    {
        return $this->quantity > 0;
    }

    /**
     * Get formatted price
     */
    public function getFormattedPrice(): string
    {
        return number_format((float) $this->price, 2, '.', '') . ' zł';
    }

    /**
     * Get availability status
     */
    public function getAvailabilityStatus(): array
    {
        if ($this->quantity === 0) {
            return ['status' => 'unavailable', 'text' => 'Brak w magazynie', 'class' => 'danger'];
        }
        
        if ($this->quantity <= 3) {
            return ['status' => 'low', 'text' => "Ostatnie sztuki: {$this->quantity}", 'class' => 'warning'];
        }
        
        return ['status' => 'available', 'text' => "Dostępne: {$this->quantity}", 'class' => 'success'];
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
