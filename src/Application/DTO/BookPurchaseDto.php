<?php

declare(strict_types=1);

namespace App\Application\DTO;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class BookPurchaseDto
{
    #[Assert\NotBlank(message: 'ID książki jest wymagane')]
    public ?Uuid $bookUuid = null;

    #[Assert\NotBlank(message: 'ID kupującego jest wymagane')]
    public ?Uuid $buyerUuid = null;

    #[Assert\NotBlank(message: 'Nazwa kupującego jest wymagana')]
    #[Assert\Length(min: 1, max: 255, minMessage: 'Nazwa kupującego musi mieć co najmniej 1 znak', maxMessage: 'Nazwa kupującego nie może przekraczać 255 znaków')]
    public ?string $buyerName = null;

    #[Assert\NotBlank(message: 'Email kupującego jest wymagany')]
    #[Assert\Email(message: 'Podaj prawidłowy adres email')]
    #[Assert\Length(max: 255, maxMessage: 'Email nie może przekraczać 255 znaków')]
    public ?string $buyerEmail = null;

    #[Assert\NotBlank(message: 'Ilość jest wymagana')]
    #[Assert\Positive(message: 'Ilość musi być dodatnia')]
    #[Assert\LessThanOrEqual(value: 999, message: 'Ilość nie może przekraczać 999')]
    public ?int $quantity = null;

    #[Assert\NotBlank(message: 'Cena zakupu jest wymagana')]
    #[Assert\Positive(message: 'Cena zakupu musi być dodatnia')]
    #[Assert\LessThanOrEqual(value: 9999.99, message: 'Cena zakupu nie może przekraczać 9999.99 zł')]
    public ?float $purchasePrice = null;

    #[Assert\Choice(choices: ['pending', 'completed', 'cancelled', 'refunded'], message: 'Nieprawidłowy status')]
    public ?string $status = 'pending';

    public ?string $notes = null;
    public ?string $paymentMethod = null;
    public ?string $transactionId = null;

    public ?Uuid $id = null;
    public ?\DateTimeImmutable $purchaseDate = null;
    public ?\DateTimeImmutable $completedAt = null;
    public ?float $totalPrice = null;

    // Book information for response
    public ?string $bookTitle = null;
    public ?string $bookCoverImage = null;
    public ?string $bookCategory = null;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        
        if (isset($data['id'])) {
            $dto->id = is_string($data['id']) ? Uuid::fromString($data['id']) : $data['id'];
        }
        
        if (isset($data['bookUuid'])) {
            $dto->bookUuid = is_string($data['bookUuid']) ? Uuid::fromString($data['bookUuid']) : $data['bookUuid'];
        }
        
        if (isset($data['buyerUuid'])) {
            $dto->buyerUuid = is_string($data['buyerUuid']) ? Uuid::fromString($data['buyerUuid']) : $data['buyerUuid'];
        }
        
        $dto->buyerName = $data['buyerName'] ?? null;
        $dto->buyerEmail = $data['buyerEmail'] ?? null;
        $dto->quantity = isset($data['quantity']) ? (int) $data['quantity'] : null;
        $dto->purchasePrice = isset($data['purchasePrice']) ? (float) $data['purchasePrice'] : null;
        $dto->status = $data['status'] ?? 'pending';
        $dto->notes = $data['notes'] ?? null;
        $dto->paymentMethod = $data['paymentMethod'] ?? null;
        $dto->transactionId = $data['transactionId'] ?? null;
        
        if (isset($data['purchaseDate'])) {
            $dto->purchaseDate = is_string($data['purchaseDate']) ? new \DateTimeImmutable($data['purchaseDate']) : $data['purchaseDate'];
        }
        
        if (isset($data['completedAt'])) {
            $dto->completedAt = is_string($data['completedAt']) ? new \DateTimeImmutable($data['completedAt']) : $data['completedAt'];
        }
        
        $dto->totalPrice = isset($data['totalPrice']) ? (float) $data['totalPrice'] : null;
        
        // Book information
        $dto->bookTitle = $data['bookTitle'] ?? null;
        $dto->bookCoverImage = $data['bookCoverImage'] ?? null;
        $dto->bookCategory = $data['bookCategory'] ?? null;
        
        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id?->toRfc4122(),
            'bookUuid' => $this->bookUuid?->toRfc4122(),
            'buyerUuid' => $this->buyerUuid?->toRfc4122(),
            'buyerName' => $this->buyerName,
            'buyerEmail' => $this->buyerEmail,
            'quantity' => $this->quantity,
            'purchasePrice' => $this->purchasePrice,
            'totalPrice' => $this->totalPrice,
            'status' => $this->status,
            'notes' => $this->notes,
            'paymentMethod' => $this->paymentMethod,
            'transactionId' => $this->transactionId,
            'purchaseDate' => $this->purchaseDate?->format('Y-m-d\TH:i:s\Z'),
            'completedAt' => $this->completedAt?->format('Y-m-d\TH:i:s\Z'),
            'bookTitle' => $this->bookTitle,
            'bookCoverImage' => $this->bookCoverImage,
            'bookCategory' => $this->bookCategory,
        ];
    }

    public function toResponseArray(): array
    {
        $data = $this->toArray();
        
        // Add formatted prices
        $data['formattedPurchasePrice'] = $this->purchasePrice ? number_format($this->purchasePrice, 2, '.', '') . ' zł' : null;
        $data['formattedTotalPrice'] = $this->totalPrice ? number_format($this->totalPrice, 2, '.', '') . ' zł' : null;
        
        // Add status display information
        $data['statusDisplay'] = $this->getStatusDisplay();
        
        return $data;
    }

    private function getStatusDisplay(): array
    {
        return match ($this->status) {
            'pending' => ['text' => 'Oczekuje', 'class' => 'warning', 'icon' => 'clock'],
            'completed' => ['text' => 'Zakończone', 'class' => 'success', 'icon' => 'check-circle'],
            'cancelled' => ['text' => 'Anulowane', 'class' => 'danger', 'icon' => 'x-circle'],
            'refunded' => ['text' => 'Zwrócone', 'class' => 'info', 'icon' => 'arrow-clockwise'],
            default => ['text' => 'Nieznany', 'class' => 'secondary', 'icon' => 'question-circle'],
        };
    }

    // Getters and setters
    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getBookUuid(): ?Uuid
    {
        return $this->bookUuid;
    }

    public function setBookUuid(Uuid $bookUuid): self
    {
        $this->bookUuid = $bookUuid;
        return $this;
    }

    public function getBuyerUuid(): ?Uuid
    {
        return $this->buyerUuid;
    }

    public function setBuyerUuid(Uuid $buyerUuid): self
    {
        $this->buyerUuid = $buyerUuid;
        return $this;
    }

    public function getBuyerName(): ?string
    {
        return $this->buyerName;
    }

    public function setBuyerName(string $buyerName): self
    {
        $this->buyerName = $buyerName;
        return $this;
    }

    public function getBuyerEmail(): ?string
    {
        return $this->buyerEmail;
    }

    public function setBuyerEmail(string $buyerEmail): self
    {
        $this->buyerEmail = $buyerEmail;
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

    public function getPurchasePrice(): ?float
    {
        return $this->purchasePrice;
    }

    public function setPurchasePrice(float $purchasePrice): self
    {
        $this->purchasePrice = $purchasePrice;
        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;
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

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId): self
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    public function getPurchaseDate(): ?\DateTimeImmutable
    {
        return $this->purchaseDate;
    }

    public function setPurchaseDate(\DateTimeImmutable $purchaseDate): self
    {
        $this->purchaseDate = $purchaseDate;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }
}
