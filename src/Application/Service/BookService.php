<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\BookDto;
use App\Domain\Entity\Book;
use App\Domain\Repository\BookRepository;
use Symfony\Component\Uid\Uuid;

class BookService
{
    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly UsersMicroserviceService $usersService
    ) {
        // Inject users service into repository for company filtering
        $this->bookRepository->setUsersService($usersService);
    }

    /**
     * Create a new book
     */
    public function createBook(BookDto $dto): Book
    {
        // Owner UUID is validated by JWT authentication
        $book = new Book(
            $dto->title,
            (string) $dto->price,
            $dto->quantity,
            $dto->ownerUuid,
            $dto->ownerName,
            $dto->description,
            $dto->coverImage,
            $dto->category
        );

        $this->bookRepository->save($book);
        return $book;
    }

    /**
     * Update an existing book
     */
    public function updateBook(Uuid $uuid, BookDto $dto): Book
    {
        $book = $this->getBookByUuid($uuid);

        // Update fields
        if ($dto->title !== null) {
            $book->setTitle($dto->title);
        }
        
        if ($dto->description !== null) {
            $book->setDescription($dto->description);
        }
        
        if ($dto->price !== null) {
            $book->setPrice((string) $dto->price);
        }
        
        if ($dto->quantity !== null) {
            $book->setQuantity($dto->quantity);
        }
        
        if ($dto->coverImage !== null) {
            $book->setCoverImage($dto->coverImage);
        }
        
        if ($dto->category !== null) {
            $book->setCategory($dto->category);
        }
        
        if ($dto->ownerUuid !== null) {
            // Owner UUID is validated by JWT authentication
            $book->setOwnerUuid($dto->ownerUuid);
        }
        
        if ($dto->ownerName !== null) {
            $book->setOwnerName($dto->ownerName);
        }

        $this->bookRepository->save($book);
        return $book;
    }

    /**
     * Delete a book
     */
    public function deleteBook(Uuid $uuid): void
    {
        $book = $this->getBookByUuid($uuid);
        $this->bookRepository->delete($book);
    }

    /**
     * Get book by UUID
     */
    public function getBookByUuid(Uuid $uuid): Book
    {
        $book = $this->bookRepository->findOneByUuid($uuid);
        
        if ($book === null) {
            throw new \RuntimeException('Book not found');
        }
        
        return $book;
    }

    /**
     * Get all books with filters
     */
    public function getBooks(array $filters = []): array
    {
        return $this->bookRepository->findWithFilters($filters);
    }

    /**
     * Get books by owner
     */
    public function getBooksByOwner(Uuid $ownerUuid): array
    {
        return $this->bookRepository->findByOwner($ownerUuid);
    }

    /**
     * Get available books
     */
    public function getAvailableBooks(): array
    {
        return $this->bookRepository->findAvailable();
    }

    /**
     * Search books
     */
    public function searchBooks(string $query): array
    {
        return $this->bookRepository->searchBooks($query);
    }

    /**
     * Get books by category
     */
    public function getBooksByCategory(string $category): array
    {
        return $this->bookRepository->findByCategory($category);
    }

    /**
     * Get all available categories
     */
    public function getAvailableCategories(): array
    {
        return $this->bookRepository->getAvailableCategories();
    }

    /**
     * Get recently added books
     */
    public function getRecentlyAddedBooks(int $limit = 10): array
    {
        return $this->bookRepository->findRecentlyAdded($limit);
    }

    /**
     * Get books by price range
     */
    public function getBooksByPriceRange(float $minPrice, float $maxPrice): array
    {
        return $this->bookRepository->findByPriceRange($minPrice, $maxPrice);
    }

    /**
     * Get owner statistics
     */
    public function getOwnerStatistics(Uuid $ownerUuid): array
    {
        return $this->bookRepository->getOwnerStatistics($ownerUuid);
    }

    /**
     * Get platform statistics
     */
    public function getPlatformStatistics(): array
    {
        return $this->bookRepository->getPlatformStatistics();
    }

    /**
     * Decrease book quantity (for purchases)
     */
    public function decreaseBookQuantity(Uuid $bookUuid, int $quantity): Book
    {
        $book = $this->getBookByUuid($bookUuid);
        
        if ($book->getQuantity() < $quantity) {
            throw new \RuntimeException('Insufficient book quantity');
        }
        
        $book->decreaseQuantity($quantity);
        $this->bookRepository->save($book);
        
        return $book;
    }

    /**
     * Increase book quantity (for returns/additions)
     */
    public function increaseBookQuantity(Uuid $bookUuid, int $quantity): Book
    {
        $book = $this->getBookByUuid($bookUuid);
        $book->increaseQuantity($quantity);
        $this->bookRepository->save($book);
        
        return $book;
    }

    /**
     * Check if book is available
     */
    public function isBookAvailable(Uuid $bookUuid, int $requestedQuantity = 1): bool
    {
        $book = $this->getBookByUuid($bookUuid);
        return $book->getQuantity() >= $requestedQuantity;
    }

    /**
     * Convert Book entity to DTO
     */
    public function bookToDto(Book $book): BookDto
    {
        $dto = new BookDto();
        $dto->id = $book->getId();
        $dto->title = $book->getTitle();
        $dto->description = $book->getDescription();
        $dto->price = (float) $book->getPrice();
        $dto->quantity = $book->getQuantity();
        $dto->coverImage = $book->getCoverImage();
        $dto->category = $book->getCategory();
        $dto->ownerUuid = $book->getOwnerUuid();
        $dto->ownerName = $book->getOwnerName();
        $dto->createdAt = $book->getCreatedAt();
        $dto->updatedAt = $book->getUpdatedAt();
        
        return $dto;
    }

    /**
     * Convert Book entity to response array
     */
    public function bookToArray(Book $book): array
    {
        return [
            'id' => $book->getId()->toRfc4122(),
            'title' => $book->getTitle(),
            'description' => $book->getDescription(),
            'price' => (float) $book->getPrice(),
            'formattedPrice' => $book->getFormattedPrice(),
            'quantity' => $book->getQuantity(),
            'coverImage' => $book->getCoverImage(),
            'category' => $book->getCategory(),
            'ownerUuid' => $book->getOwnerUuid()->toRfc4122(),
            'ownerName' => $book->getOwnerName(),
            'createdAt' => $book->getCreatedAt()->format('Y-m-d\TH:i:s\Z'),
            'updatedAt' => $book->getUpdatedAt()?->format('Y-m-d\TH:i:s\Z'),
            'isAvailable' => $book->isAvailable(),
            'availabilityStatus' => $book->getAvailabilityStatus(),
        ];
    }

    /**
     * Convert multiple books to response arrays
     */
    public function booksToArray(array $books): array
    {
        return array_map([$this, 'bookToArray'], $books);
    }

    /**
     * Validate book data
     */
    public function validateBookData(BookDto $dto): array
    {
        $errors = [];

        // Owner UUID is validated by JWT authentication, no need to check in users service
        
        // Check if category is valid (if provided)
        if ($dto->category !== null) {
            $availableCategories = $this->getAvailableCategories();
            if (!empty($availableCategories) && !in_array($dto->category, $availableCategories, true)) {
                $errors['category'] = 'Invalid category';
            }
        }

        return $errors;
    }
}
