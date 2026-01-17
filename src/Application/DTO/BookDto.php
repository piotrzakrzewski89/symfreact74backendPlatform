<?php

declare(strict_types=1);

namespace App\Application\DTO;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class BookDto
{
    #[Assert\NotBlank(message: 'Tytuł jest wymagany')]
    #[Assert\Length(min: 1, max: 255, minMessage: 'Tytuł musi mieć co najmniej 1 znak', maxMessage: 'Tytuł nie może przekraczać 255 znaków')]
    public ?string $title = null;

    #[Assert\Length(max: 1000, maxMessage: 'Opis nie może przekraczać 1000 znaków')]
    public ?string $description = null;

    #[Assert\NotBlank(message: 'Cena jest wymagana')]
    #[Assert\Positive(message: 'Cena musi być dodatnia')]
    #[Assert\LessThanOrEqual(value: 9999.99, message: 'Cena nie może przekraczać 9999.99 zł')]
    public ?float $price = null;

    #[Assert\NotBlank(message: 'Ilość jest wymagana')]
    #[Assert\PositiveOrZero(message: 'Ilość nie może być ujemna')]
    #[Assert\LessThanOrEqual(value: 999, message: 'Ilość nie może przekraczać 999')]
    public ?int $quantity = null;

    #[Assert\Length(max: 255, maxMessage: 'URL obrazka nie może przekraczać 255 znaków')]
    public ?string $coverImage = null;

    #[Assert\Length(max: 100, maxMessage: 'Kategoria nie może przekraczać 100 znaków')]
    public ?string $category = null;

    #[Assert\NotBlank(message: 'ID właściciela jest wymagane')]
    public ?Uuid $ownerUuid = null;

    #[Assert\NotBlank(message: 'Nazwa właściciela jest wymagana')]
    #[Assert\Length(min: 1, max: 255, minMessage: 'Nazwa właściciela musi mieć co najmniej 1 znak', maxMessage: 'Nazwa właściciela nie może przekraczać 255 znaków')]
    public ?string $ownerName = null;

    public ?Uuid $id = null;
    public ?\DateTimeImmutable $createdAt = null;
    public ?\DateTimeImmutable $updatedAt = null;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        
        if (isset($data['id'])) {
            $dto->id = is_string($data['id']) ? Uuid::fromString($data['id']) : $data['id'];
        }
        
        $dto->title = $data['title'] ?? null;
        $dto->description = $data['description'] ?? null;
        $dto->price = isset($data['price']) ? (float) $data['price'] : null;
        $dto->quantity = isset($data['quantity']) ? (int) $data['quantity'] : null;
        $dto->coverImage = $data['coverImage'] ?? null;
        $dto->category = $data['category'] ?? null;
        
        if (isset($data['ownerUuid'])) {
            $dto->ownerUuid = is_string($data['ownerUuid']) ? Uuid::fromString($data['ownerUuid']) : $data['ownerUuid'];
        }
        
        $dto->ownerName = $data['ownerName'] ?? null;
        
        if (isset($data['createdAt'])) {
            $dto->createdAt = is_string($data['createdAt']) ? new \DateTimeImmutable($data['createdAt']) : $data['createdAt'];
        }
        
        if (isset($data['updatedAt'])) {
            $dto->updatedAt = is_string($data['updatedAt']) ? new \DateTimeImmutable($data['updatedAt']) : $data['updatedAt'];
        }
        
        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id?->toRfc4122(),
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'coverImage' => $this->coverImage,
            'category' => $this->category,
            'ownerUuid' => $this->ownerUuid?->toRfc4122(),
            'ownerName' => $this->ownerName,
            'createdAt' => $this->createdAt?->format('Y-m-d\TH:i:s\Z'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): self
    {
        $this->coverImage = $coverImage;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getOwnerUuid(): ?Uuid
    {
        return $this->ownerUuid;
    }

    public function setOwnerUuid(Uuid $ownerUuid): self
    {
        $this->ownerUuid = $ownerUuid;
        return $this;
    }

    public function getOwnerName(): ?string
    {
        return $this->ownerName;
    }

    public function setOwnerName(string $ownerName): self
    {
        $this->ownerName = $ownerName;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
