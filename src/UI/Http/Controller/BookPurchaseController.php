<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\DTO\BookPurchaseDto;
use App\Application\Service\BookPurchaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/purchases', name: 'api_purchases_')]
class BookPurchaseController extends AbstractController
{
    public function __construct(
        private readonly BookPurchaseService $purchaseService,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * Get all purchases with optional filters
     */
    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $purchases = $this->purchaseService->getPurchases($filters);

        return $this->json([
            'data' => $this->purchaseService->purchasesToArray($purchases),
            'meta' => [
                'total' => count($purchases),
                'filters' => $filters
            ]
        ]);
    }

    /**
     * Get a specific purchase by UUID
     */
    #[Route('/{uuid}', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(string $uuid): JsonResponse
    {
        try {
            $purchase = $this->purchaseService->getPurchaseByUuid(Uuid::fromString($uuid));
            
            // Check if user has permission to view this purchase
            $this->checkPurchasePermission($purchase);
            
            return $this->json([
                'data' => $this->purchaseService->purchaseToArray($purchase)
            ]);
        } catch (\Exception $e) {
            if ($e->getMessage() === 'Purchase not found') {
                return $this->json(['error' => 'Purchase not found'], Response::HTTP_NOT_FOUND);
            }
            if ($e->getMessage() === 'Access denied') {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }
            return $this->json(['error' => 'Failed to get purchase'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create multiple purchases at once (bulk order)
     */
    #[Route('/bulk', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createBulk(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if ($data === null || !isset($data['purchases']) || !is_array($data['purchases'])) {
                return $this->json(['error' => 'Invalid request format. purchases array is required'], Response::HTTP_BAD_REQUEST);
            }

            $createdPurchases = [];
            $errors = [];
            
            foreach ($data['purchases'] as $index => $purchaseData) {
                try {
                    $dto = BookPurchaseDto::fromArray($purchaseData);
                    
                    // Validate DTO
                    $validationErrors = $this->validateDto($dto);
                    if (!empty($validationErrors)) {
                        $errors[$index] = $validationErrors;
                        continue;
                    }

                    // Validate business logic
                    $businessErrors = $this->purchaseService->validatePurchaseData($dto);
                    if (!empty($businessErrors)) {
                        $errors[$index] = $businessErrors;
                        continue;
                    }

                    $purchase = $this->purchaseService->createPurchase($dto);
                    $createdPurchases[] = $this->purchaseService->purchaseToArray($purchase);
                    
                } catch (\Exception $e) {
                    $errors[$index] = ['general' => $e->getMessage()];
                }
            }
            
            if (!empty($errors)) {
                return $this->json([
                    'errors' => $errors,
                    'data' => $createdPurchases,
                    'message' => 'Some purchases could not be completed'
                ], Response::HTTP_PARTIAL_CONTENT);
            }
            
            return $this->json([
                'data' => $createdPurchases,
                'message' => 'All purchases created successfully',
                'orderSummary' => $this->calculateOrderSummary($createdPurchases)
            ], Response::HTTP_CREATED);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to create bulk purchases'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a new purchase
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

            $dto = BookPurchaseDto::fromArray($data);
            
            // Validate DTO
            $errors = $this->validateDto($dto);
            if (!empty($errors)) {
                return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            // Validate business logic
            $businessErrors = $this->purchaseService->validatePurchaseData($dto);
            if (!empty($businessErrors)) {
                return $this->json(['errors' => $businessErrors], Response::HTTP_BAD_REQUEST);
            }

            $purchase = $this->purchaseService->createPurchase($dto);
            
            return $this->json([
                'data' => $this->purchaseService->purchaseToArray($purchase),
                'message' => 'Purchase created successfully'
            ], Response::HTTP_CREATED);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to create purchase'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update purchase status
     */
    #[Route('/{uuid}/status', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateStatus(string $uuid, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if ($data === null || !isset($data['status'])) {
                return $this->json(['error' => 'Status is required'], Response::HTTP_BAD_REQUEST);
            }

            $purchase = $this->purchaseService->getPurchaseByUuid(Uuid::fromString($uuid));
            
            // Check if user has permission to update this purchase
            $this->checkPurchasePermission($purchase, true);

            $updatedPurchase = $this->purchaseService->updatePurchaseStatus(
                $purchase->getId(),
                $data['status']
            );
            
            return $this->json([
                'data' => $this->purchaseService->purchaseToArray($updatedPurchase),
                'message' => 'Purchase status updated successfully'
            ]);
            
        } catch (\Exception $e) {
            if ($e->getMessage() === 'Purchase not found') {
                return $this->json(['error' => 'Purchase not found'], Response::HTTP_NOT_FOUND);
            }
            if ($e->getMessage() === 'Access denied') {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }
            return $this->json(['error' => 'Failed to update purchase status'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Complete a purchase
     */
    #[Route('/{uuid}/complete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function complete(string $uuid, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $transactionId = $data['transactionId'] ?? null;

            $purchase = $this->purchaseService->getPurchaseByUuid(Uuid::fromString($uuid));
            
            // Check if user has permission to complete this purchase
            $this->checkPurchasePermission($purchase, true);

            $completedPurchase = $this->purchaseService->completePurchase($purchase->getId(), $transactionId);
            
            return $this->json([
                'data' => $this->purchaseService->purchaseToArray($completedPurchase),
                'message' => 'Purchase completed successfully'
            ]);
            
        } catch (\Exception $e) {
            if ($e->getMessage() === 'Purchase not found') {
                return $this->json(['error' => 'Purchase not found'], Response::HTTP_NOT_FOUND);
            }
            if ($e->getMessage() === 'Access denied') {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }
            return $this->json(['error' => 'Failed to complete purchase'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Cancel a purchase
     */
    #[Route('/{uuid}/cancel', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(string $uuid): JsonResponse
    {
        try {
            $purchase = $this->purchaseService->getPurchaseByUuid(Uuid::fromString($uuid));
            
            // Check if user has permission to cancel this purchase
            $this->checkPurchasePermission($purchase);

            $cancelledPurchase = $this->purchaseService->cancelPurchase($purchase->getId());
            
            return $this->json([
                'data' => $this->purchaseService->purchaseToArray($cancelledPurchase),
                'message' => 'Purchase cancelled successfully'
            ]);
            
        } catch (\Exception $e) {
            if ($e->getMessage() === 'Purchase not found') {
                return $this->json(['error' => 'Purchase not found'], Response::HTTP_NOT_FOUND);
            }
            if ($e->getMessage() === 'Access denied') {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }
            return $this->json(['error' => 'Failed to cancel purchase'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get buyer statistics
     */
    #[Route('/buyer/{buyerUuid}/statistics', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function buyerStatistics(string $buyerUuid): JsonResponse
    {
        try {
            $buyerUuidObj = Uuid::fromString($buyerUuid);
            
            $stats = $this->purchaseService->getBuyerStatistics($buyerUuidObj);
            
            return $this->json([
                'data' => $stats,
                'meta' => [
                    'buyerUuid' => $buyerUuid
                ]
            ]);
            
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Invalid buyer UUID format'], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to get statistics: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get seller statistics
     */
    #[Route('/seller/{sellerUuid}/statistics', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function sellerStatistics(string $sellerUuid): JsonResponse
    {
        try {
            $sellerUuidObj = Uuid::fromString($sellerUuid);
            
            // Check if user can view these statistics (either own stats or admin)
            if (!$this->isGranted('ROLE_ADMIN') && $this->getUser()?->getId() !== $sellerUuid) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            $stats = $this->purchaseService->getSellerStatistics($sellerUuidObj);
            
            return $this->json([
                'data' => $stats,
                'meta' => [
                    'sellerUuid' => $sellerUuid
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid seller UUID'], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get platform purchase statistics
     */
    #[Route('/statistics', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function platformStatistics(): JsonResponse
    {
        $stats = $this->purchaseService->getPlatformPurchaseStatistics();
        
        return $this->json([
            'data' => $stats
        ]);
    }

    /**
     * Get purchase analytics for date range
     */
    #[Route('/analytics', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function analytics(Request $request): JsonResponse
    {
        try {
            $from = new \DateTimeImmutable($request->query->get('from', 'now - 30 days'));
            $to = new \DateTimeImmutable($request->query->get('to', 'now'));
            
            $analytics = $this->purchaseService->getPurchaseAnalytics($from, $to);
            
            return $this->json([
                'data' => $analytics
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date range'], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Process bulk completion
     */
    #[Route('/bulk-complete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function bulkComplete(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if ($data === null || !isset($data['purchaseUuids'])) {
                return $this->json(['error' => 'Purchase UUIDs are required'], Response::HTTP_BAD_REQUEST);
            }

            $result = $this->purchaseService->processBulkCompletion($data['purchaseUuids']);
            
            return $this->json([
                'data' => $result,
                'message' => 'Bulk completion processed'
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to process bulk completion'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Calculate order summary from purchases
     */
    private function calculateOrderSummary(array $purchases): array
    {
        $totalItems = 0;
        $totalPrice = 0.0;
        $books = [];
        
        foreach ($purchases as $purchase) {
            $totalItems += $purchase['quantity'];
            $totalPrice += $purchase['totalPrice'];
            $books[] = [
                'title' => $purchase['bookTitle'],
                'quantity' => $purchase['quantity'],
                'unitPrice' => $purchase['purchasePrice'],
                'totalPrice' => $purchase['totalPrice']
            ];
        }
        
        return [
            'totalItems' => $totalItems,
            'totalPrice' => $totalPrice,
            'books' => $books
        ];
    }

    /**
     * Check if user has permission to access purchase
     */
    private function checkPurchasePermission($purchase, bool $requireAdmin = false): void
    {
        $user = $this->getUser();
        
        if ($requireAdmin && !$this->isGranted('ROLE_ADMIN')) {
            throw new \Exception('Access denied');
        }
        
        // Allow access if user is admin
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }
        
        // Allow access if user is the buyer
        if ($user && $user->getId() === $purchase->getBuyerUuid()->toRfc4122()) {
            return;
        }
        
        // Allow access if user is the seller (book owner)
        if ($user && $user->getId() === $purchase->getBook()->getOwnerUuid()->toRfc4122()) {
            return;
        }
        
        throw new \Exception('Access denied');
    }

    /**
     * Extract filters from request
     */
    private function extractFilters(Request $request): array
    {
        return [
            'buyerUuid' => $request->query->get('buyerUuid') ? Uuid::fromString($request->query->get('buyerUuid')) : null,
            'sellerUuid' => $request->query->get('sellerUuid') ? Uuid::fromString($request->query->get('sellerUuid')) : null,
            'bookUuid' => $request->query->get('bookUuid') ? Uuid::fromString($request->query->get('bookUuid')) : null,
            'status' => $request->query->get('status'),
            'dateFrom' => $request->query->get('dateFrom') ? new \DateTimeImmutable($request->query->get('dateFrom')) : null,
            'dateTo' => $request->query->get('dateTo') ? new \DateTimeImmutable($request->query->get('dateTo')) : null,
            'priceMin' => $request->query->get('priceMin') ? (float) $request->query->get('priceMin') : null,
            'priceMax' => $request->query->get('priceMax') ? (float) $request->query->get('priceMax') : null,
            'sortBy' => $request->query->get('sortBy', 'purchaseDate'),
            'sortOrder' => $request->query->get('sortOrder', 'DESC'),
        ];
    }

    /**
     * Validate DTO
     */
    private function validateDto(BookPurchaseDto $dto): array
    {
        $errors = [];
        $violations = $this->validator->validate($dto);
        
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }
        
        return $errors;
    }
}
