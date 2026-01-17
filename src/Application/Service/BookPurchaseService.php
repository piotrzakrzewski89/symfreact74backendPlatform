<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\BookPurchaseDto;
use App\Domain\Entity\Book;
use App\Domain\Entity\BookPurchase;
use App\Domain\Enum\BookPurchaseStatus;
use App\Domain\Repository\BookPurchaseRepository;
use App\Domain\Repository\BookRepository;
use Symfony\Component\Uid\Uuid;

class BookPurchaseService
{
    public function __construct(
        private readonly BookPurchaseRepository $purchaseRepository,
        private readonly BookRepository $bookRepository,
        private readonly BookService $bookService,
        private readonly UsersMicroserviceService $usersService
    ) {
    }

    /**
     * Create a new book purchase
     */
    public function createPurchase(BookPurchaseDto $dto): BookPurchase
    {
        // Validate book exists and is available
        $book = $this->bookService->getBookByUuid($dto->bookUuid);
        
        if (!$this->bookService->isBookAvailable($dto->bookUuid, $dto->quantity)) {
            throw new \RuntimeException('Book not available in requested quantity');
        }

        // Validate purchase price matches current book price (with 1% tolerance)
        $currentPrice = (float) $book->getPrice();
        $purchasePrice = (float) $dto->purchasePrice;
        $priceDifference = abs($currentPrice - $purchasePrice);
        $tolerance = $currentPrice * 0.01; // 1% tolerance
        
        if ($priceDifference > $tolerance) {
            throw new \RuntimeException(sprintf(
                'Purchase price does not match current book price. Current: %.2f, Provided: %.2f',
                $currentPrice,
                $purchasePrice
            ));
        }

        // Użyj enum z domyślnym statusem PENDING
        $status = BookPurchaseStatus::from($dto->status ?? BookPurchaseStatus::getDefault()->value);

        $purchase = new BookPurchase();
        $purchase->setBook($book);
        $purchase->setBuyerUuid($dto->buyerUuid);
        $purchase->setBuyerName($dto->buyerName);
        $purchase->setBuyerEmail($dto->buyerEmail);
        $purchase->setQuantity($dto->quantity);
        $purchase->setPrice((string) $dto->purchasePrice);
        $purchase->setStatus($status->value);

        // Set additional properties
        if ($dto->notes !== null) {
            $purchase->setNotes($dto->notes);
        }
        
        if ($dto->paymentMethod !== null) {
            $purchase->setPaymentMethod($dto->paymentMethod);
        }
        
        if ($dto->transactionId !== null) {
            $purchase->setTransactionId($dto->transactionId);
        }

        // Save the purchase to database
        $this->purchaseRepository->save($purchase);

        // Update book quantity - decrement stock
        $book->setQuantity($book->getQuantity() - $dto->quantity);
        $this->bookRepository->save($book);

        return $purchase;
    }

    /**
     * Validate purchase data
     */
    public function validatePurchaseData(BookPurchaseDto $dto): array
    {
        $errors = [];

        // Check if book exists
        if ($dto->bookUuid !== null) {
            try {
                $book = $this->bookService->getBookByUuid($dto->bookUuid);
                
                // Check if book is available
                if (!$this->bookService->isBookAvailable($dto->bookUuid, $dto->quantity ?? 1)) {
                    $errors['quantity'] = 'Book not available in requested quantity';
                }
                
                // Check if purchase price matches (with 1% tolerance)
                if ($dto->purchasePrice !== null) {
                    $currentPrice = (float) $book->getPrice();
                    $purchasePrice = (float) $dto->purchasePrice;
                    $priceDifference = abs($currentPrice - $purchasePrice);
                    $tolerance = $currentPrice * 0.01; // 1% tolerance
                    
                    if ($priceDifference > $tolerance) {
                        $errors['purchasePrice'] = sprintf(
                            'Purchase price does not match current book price. Current: %.2f, Provided: %.2f',
                            $currentPrice,
                            $purchasePrice
                        );
                    }
                }
            } catch (\Exception $e) {
                $errors['bookUuid'] = 'Book not found';
            }
        }

        // Validate quantity
        if ($dto->quantity !== null && $dto->quantity <= 0) {
            $errors['quantity'] = 'Quantity must be positive';
        }

        // Validate status używając enum
        if ($dto->status !== null) {
            try {
                BookPurchaseStatus::from($dto->status);
            } catch (\ValueError $e) {
                $errors['status'] = 'Invalid status. Valid statuses: ' . implode(', ', BookPurchaseStatus::getValidStatuses());
            }
        }

        return $errors;
    }

    /**
     * Complete a purchase
     */
    public function completePurchase(Uuid $uuid, ?string $transactionId = null): BookPurchase
    {
        $purchase = $this->purchaseRepository->findByUuid($uuid);
        
        if (!$purchase) {
            throw new \RuntimeException('Purchase not found');
        }

        if ($purchase->getStatus() !== BookPurchaseStatus::PENDING) {
            throw new \RuntimeException('Only pending purchases can be completed');
        }

        $purchase->setStatus(BookPurchaseStatus::COMPLETED);
        
        if ($transactionId) {
            $purchase->setTransactionId($transactionId);
        }
        
        $this->purchaseRepository->save($purchase);

        return $purchase;
    }

    /**
     * Cancel a purchase
     */
    public function cancelPurchase(Uuid $uuid): BookPurchase
    {
        $purchase = $this->purchaseRepository->findByUuid($uuid);
        
        if (!$purchase) {
            throw new \RuntimeException('Purchase not found');
        }

        if ($purchase->getStatus() !== BookPurchaseStatus::PENDING) {
            throw new \RuntimeException('Only pending purchases can be cancelled');
        }

        $purchase->setStatus(BookPurchaseStatus::CANCELLED);
        $this->purchaseRepository->save($purchase);

        return $purchase;
    }

    /**
     * Convert purchase to array
     */
    public function purchaseToArray(BookPurchase $purchase): array
    {
        return [
            'uuid' => $purchase->getId(),
            'bookUuid' => $purchase->getBook()->getId(),
            'bookTitle' => $purchase->getBook()->getTitle(),
            'buyerUuid' => $purchase->getBuyerUuid(),
            'buyerName' => $purchase->getBuyerName(),
            'buyerEmail' => $purchase->getBuyerEmail(),
            'quantity' => $purchase->getQuantity(),
            'purchasePrice' => $purchase->getPrice(),
            'totalPrice' => $purchase->getQuantity() * (float) $purchase->getPrice(),
            'status' => $purchase->getStatus(),
            'statusLabel' => BookPurchaseStatus::from($purchase->getStatus())->getLabel(),
            'paymentMethod' => $purchase->getPaymentMethod(),
            'transactionId' => $purchase->getTransactionId(),
            'notes' => $purchase->getNotes(),
            'createdAt' => $purchase->getCreatedAt(),
            'updatedAt' => $purchase->getUpdatedAt(),
        ];
    }

    /**
     * Get all purchases with filters
     */
    public function getPurchases(array $filters = []): array
    {
        return $this->purchaseRepository->findWithFilters($filters);
    }

    /**
     * Convert multiple purchases to arrays
     */
    public function purchasesToArray(array $purchases): array
    {
        return array_map([$this, 'purchaseToArray'], $purchases);
    }

    /**
     * Get purchase by UUID
     */
    public function getPurchaseByUuid(Uuid $uuid): BookPurchase
    {
        $purchase = $this->purchaseRepository->findOneByUuid($uuid);
        
        if (!$purchase) {
            throw new \RuntimeException('Purchase not found');
        }
        
        return $purchase;
    }

    /**
     * Update purchase status
     */
    public function updatePurchaseStatus(Uuid $uuid, string $status): BookPurchase
    {
        $purchase = $this->getPurchaseByUuid($uuid);
        
        // Validate status
        try {
            $statusEnum = BookPurchaseStatus::from($status);
        } catch (\ValueError $e) {
            throw new \InvalidArgumentException('Invalid status');
        }
        
        $purchase->setStatus($statusEnum->value);
        $this->purchaseRepository->save($purchase);
        
        return $purchase;
    }

    /**
     * Get purchases by buyer
     */
    public function getPurchasesByBuyer(Uuid $buyerUuid): array
    {
        return $this->purchaseRepository->findByBuyer($buyerUuid);
    }

    /**
     * Get purchases by seller
     */
    public function getPurchasesBySeller(Uuid $sellerUuid): array
    {
        return $this->purchaseRepository->findBySeller($sellerUuid);
    }

    /**
     * Get purchases by book
     */
    public function getPurchasesByBook(Uuid $bookUuid): array
    {
        return $this->purchaseRepository->findByBook($bookUuid);
    }

    /**
     * Get purchases by status
     */
    public function getPurchasesByStatus(string $status): array
    {
        return $this->purchaseRepository->findByStatus($status);
    }

    /**
     * Get pending purchases
     */
    public function getPendingPurchases(): array
    {
        return $this->purchaseRepository->findPendingPurchases();
    }

    /**
     * Get recent purchases
     */
    public function getRecentPurchases(int $limit = 10): array
    {
        return $this->purchaseRepository->findRecentPurchases($limit);
    }

    /**
     * Get buyer statistics
     */
    public function getBuyerStatistics(Uuid $buyerUuid): array
    {
        return $this->purchaseRepository->getBuyerStatistics($buyerUuid);
    }

    /**
     * Get seller statistics
     */
    public function getSellerStatistics(Uuid $sellerUuid): array
    {
        return $this->purchaseRepository->getSellerStatistics($sellerUuid);
    }

    /**
     * Get platform purchase statistics
     */
    public function getPlatformPurchaseStatistics(): array
    {
        return $this->purchaseRepository->getPlatformStatistics();
    }

    /**
     * Get purchase analytics for date range
     */
    public function getPurchaseAnalytics(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        // TODO: Implement analytics logic
        return [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'totalPurchases' => 0,
            'totalRevenue' => 0,
        ];
    }

    /**
     * Process bulk completion
     */
    public function processBulkCompletion(array $purchaseUuids): array
    {
        $results = [];
        $errors = [];
        
        foreach ($purchaseUuids as $index => $uuidString) {
            try {
                $uuid = Uuid::fromString($uuidString);
                $purchase = $this->completePurchase($uuid);
                $results[] = $this->purchaseToArray($purchase);
            } catch (\Exception $e) {
                $errors[$index] = $e->getMessage();
            }
        }
        
        return [
            'completed' => $results,
            'errors' => $errors
        ];
    }
}
