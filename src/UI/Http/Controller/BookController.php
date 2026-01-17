<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\DTO\BookDto;
use App\Application\Service\BookService;
use App\Security\JwtUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/books', name: 'api_books_')]
class BookController extends AbstractController
{
    public function __construct(
        private readonly BookService $bookService,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * Get all books with optional filters
     */
    #[Route('', methods: ['GET', 'OPTIONS'])]
    public function index(Request $request): JsonResponse
    {
        // CORS headers dla OPTIONS
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse('', 200, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
                'Access-Control-Max-Age' => '3600'
            ]);
        }

        $filters = $this->extractFilters($request);
        $books = $this->bookService->getBooks($filters);

        $response = $this->json([
            'data' => $this->bookService->booksToArray($books),
            'meta' => [
                'total' => count($books),
                'filters' => $filters
            ]
        ]);

        // CORS headers dla GET
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        
        return $response;
    }

    /**
     * Get a specific book by UUID
     */
    #[Route('/{uuid}', methods: ['GET'])]
    public function show(string $uuid): JsonResponse
    {
        try {
            $book = $this->bookService->getBookByUuid(Uuid::fromString($uuid));
            return $this->json([
                'data' => $this->bookService->bookToArray($book)
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Create a new book
     */
    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if ($data === null) {
                return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
            }

            $dto = BookDto::fromArray($data);
            
            // Validate DTO
            $errors = $this->validateDto($dto);
            if (!empty($errors)) {
                return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            // Validate business logic
            $businessErrors = $this->bookService->validateBookData($dto);
            if (!empty($businessErrors)) {
                return $this->json(['errors' => $businessErrors], Response::HTTP_BAD_REQUEST);
            }

            $book = $this->bookService->createBook($dto);
            
            return $this->json([
                'data' => $this->bookService->bookToArray($book),
                'message' => 'Book created successfully'
            ], Response::HTTP_CREATED);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to create book'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update an existing book
     */
    #[Route('/{uuid}', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function update(string $uuid, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if ($data === null) {
                return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
            }

            $dto = BookDto::fromArray($data);
            
            // Validate DTO
            $errors = $this->validateDto($dto, false);
            if (!empty($errors)) {
                return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $book = $this->bookService->updateBook(Uuid::fromString($uuid), $dto);
            
            return $this->json([
                'data' => $this->bookService->bookToArray($book),
                'message' => 'Book updated successfully'
            ]);
            
        } catch (\Exception $e) {
            if ($e->getMessage() === 'Book not found') {
                return $this->json(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
            }
            return $this->json(['error' => 'Failed to update book'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a book
     */
    #[Route('/{uuid}', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $this->bookService->deleteBook(Uuid::fromString($uuid));
            
            return $this->json([
                'message' => 'Book deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            if ($e->getMessage() === 'Book not found') {
                return $this->json(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
            }
            return $this->json(['error' => 'Failed to delete book'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get books by owner
     */
    #[Route('/owner/{ownerUuid}', methods: ['GET'])]
    public function byOwner(string $ownerUuid): JsonResponse
    {
        try {
            $books = $this->bookService->getBooksByOwner(Uuid::fromString($ownerUuid));
            
            return $this->json([
                'data' => $this->bookService->booksToArray($books),
                'meta' => [
                    'ownerUuid' => $ownerUuid,
                    'total' => count($books)
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid owner UUID'], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get available books
     */
    #[Route('/available', methods: ['GET'])]
    public function available(): JsonResponse
    {
        $books = $this->bookService->getAvailableBooks();
        
        return $this->json([
            'data' => $this->bookService->booksToArray($books),
            'meta' => [
                'total' => count($books)
            ]
        ]);
    }

    /**
     * Search books
     */
    #[Route('/search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        
        if (empty($query)) {
            return $this->json(['error' => 'Search query is required'], Response::HTTP_BAD_REQUEST);
        }

        $books = $this->bookService->searchBooks($query);
        
        return $this->json([
            'data' => $this->bookService->booksToArray($books),
            'meta' => [
                'query' => $query,
                'total' => count($books)
            ]
        ]);
    }

    /**
     * Get books by category
     */
    #[Route('/category/{category}', methods: ['GET'])]
    public function byCategory(string $category): JsonResponse
    {
        $books = $this->bookService->getBooksByCategory($category);
        
        return $this->json([
            'data' => $this->bookService->booksToArray($books),
            'meta' => [
                'category' => $category,
                'total' => count($books)
            ]
        ]);
    }

    /**
     * Get all available categories
     */
    #[Route('/categories', methods: ['GET'])]
    public function categories(): JsonResponse
    {
        $categories = $this->bookService->getAvailableCategories();
        
        return $this->json([
            'data' => $categories,
            'meta' => [
                'total' => count($categories)
            ]
        ]);
    }

    /**
     * Get recently added books
     */
    #[Route('/recent', methods: ['GET'])]
    public function recent(Request $request): JsonResponse
    {
        $limit = min((int) $request->query->get('limit', 10), 100); // Max 100 for safety
        
        $books = $this->bookService->getRecentlyAddedBooks($limit);
        
        return $this->json([
            'data' => $this->bookService->booksToArray($books),
            'meta' => [
                'limit' => $limit,
                'total' => count($books)
            ]
        ]);
    }

    /**
     * Get books by price range
     */
    #[Route('/price-range', methods: ['GET'])]
    public function priceRange(Request $request): JsonResponse
    {
        $minPrice = (float) $request->query->get('min', 0);
        $maxPrice = (float) $request->query->get('max', 9999.99);
        
        if ($minPrice < 0 || $maxPrice <= $minPrice) {
            return $this->json(['error' => 'Invalid price range'], Response::HTTP_BAD_REQUEST);
        }

        $books = $this->bookService->getBooksByPriceRange($minPrice, $maxPrice);
        
        return $this->json([
            'data' => $this->bookService->booksToArray($books),
            'meta' => [
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice,
                'total' => count($books)
            ]
        ]);
    }

    /**
     * Get owner statistics
     */
    #[Route('/owner/{ownerUuid}/statistics', methods: ['GET'])]
    public function ownerStatistics(string $ownerUuid): JsonResponse
    {
        try {
            $stats = $this->bookService->getOwnerStatistics(Uuid::fromString($ownerUuid));
            
            return $this->json([
                'data' => $stats,
                'meta' => [
                    'ownerUuid' => $ownerUuid
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid owner UUID'], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get platform statistics
     */
    #[Route('/statistics', methods: ['GET'])]
    public function platformStatistics(): JsonResponse
    {
        $stats = $this->bookService->getPlatformStatistics();
        
        return $this->json([
            'data' => $stats
        ]);
    }

    /**
     * Check if book is available
     */
    #[Route('/{uuid}/availability', methods: ['GET'])]
    public function availability(string $uuid, Request $request): JsonResponse
    {
        try {
            $book = $this->bookService->getBookByUuid(Uuid::fromString($uuid));
            $requestedQuantity = (int) $request->query->get('quantity', 1);
            
            $isAvailable = $this->bookService->isBookAvailable($book->getId(), $requestedQuantity);
            
            return $this->json([
                'data' => [
                    'uuid' => $uuid,
                    'isAvailable' => $isAvailable,
                    'currentQuantity' => $book->getQuantity(),
                    'requestedQuantity' => $requestedQuantity,
                    'availabilityStatus' => $book->getAvailabilityStatus()
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Extract filters from request
     */
    private function extractFilters(Request $request): array
    {
        $filters = [
            'search' => $request->query->get('search'),
            'category' => $request->query->get('category'),
            'availableOnly' => $request->query->getBoolean('availableOnly'),
            'priceMin' => $request->query->get('priceMin') ? (float) $request->query->get('priceMin') : null,
            'priceMax' => $request->query->get('priceMax') ? (float) $request->query->get('priceMax') : null,
            'ownerUuid' => $request->query->get('ownerUuid') ? Uuid::fromString($request->query->get('ownerUuid')) : null,
            'sortBy' => $request->query->get('sortBy', 'createdAt'),
            'sortOrder' => $request->query->get('sortOrder', 'DESC'),
            'companyFilter' => $request->query->get('companyFilter'),
            'excludeOwn' => $request->query->getBoolean('excludeOwn'),
        ];

        // Add current user info for filtering
        $user = $this->getUser();
        if ($user instanceof JwtUser) {
            $filters['currentUserUuid'] = $user->getId()->toRfc4122();
            $filters['currentUserCompanyUuid'] = $user->getCompanyUuid();
        }

        return $filters;
    }

    /**
     * Validate DTO
     */
    private function validateDto(BookDto $dto, bool $required = true): array
    {
        $errors = [];
        
        if ($required) {
            $violations = $this->validator->validate($dto);
            
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
        } else {
            // For partial updates, only validate provided fields
            if ($dto->title !== null) {
                $titleViolations = $this->validator->validateProperty($dto, 'title');
                foreach ($titleViolations as $violation) {
                    $errors['title'] = $violation->getMessage();
                }
            }
            
            if ($dto->price !== null) {
                $priceViolations = $this->validator->validateProperty($dto, 'price');
                foreach ($priceViolations as $violation) {
                    $errors['price'] = $violation->getMessage();
                }
            }
            
            if ($dto->quantity !== null) {
                $quantityViolations = $this->validator->validateProperty($dto, 'quantity');
                foreach ($quantityViolations as $violation) {
                    $errors['quantity'] = $violation->getMessage();
                }
            }
        }
        
        return $errors;
    }
}
