<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Book;
use App\Application\Service\UsersMicroserviceService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    private ?UsersMicroserviceService $usersService = null;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function setUsersService(UsersMicroserviceService $usersService): void
    {
        $this->usersService = $usersService;
    }

    /**
     * Find books by owner UUID
     */
    public function findByOwner(Uuid $ownerUuid): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.ownerUuid = :ownerUuid')
            ->setParameter('ownerUuid', $ownerUuid->toRfc4122())
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find available books (quantity > 0)
     */
    public function findAvailable(): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.quantity > :quantity')
            ->setParameter('quantity', 0)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search books by title, description, or owner name
     */
    public function searchBooks(string $query): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.title LIKE :query')
            ->orWhere('b.description LIKE :query')
            ->orWhere('b.ownerName LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find books by category
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.category = :category')
            ->andWhere('b.quantity > :quantity')
            ->setParameter('category', $category)
            ->setParameter('quantity', 0)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all available categories
     */
    public function getAvailableCategories(): array
    {
        $result = $this->createQueryBuilder('b')
            ->select('DISTINCT b.category')
            ->where('b.category IS NOT NULL')
            ->andWhere('b.quantity > :quantity')
            ->setParameter('quantity', 0)
            ->getQuery()
            ->getSingleColumnResult();

        return array_filter($result);
    }

    /**
     * Find books with filters
     */
    public function findWithFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('b');

        // Apply filters
        $this->applySearchFilter($qb, $filters['search'] ?? null);
        $this->applyCategoryFilter($qb, $filters['category'] ?? null);
        $this->applyAvailabilityFilter($qb, $filters['availableOnly'] ?? false);
        $this->applyPriceRangeFilter($qb, $filters['priceMin'] ?? null, $filters['priceMax'] ?? null);
        $this->applyOwnerFilter($qb, $filters['ownerUuid'] ?? null);
        $this->applyExcludeOwnFilter($qb, $filters['excludeOwn'] ?? false, $filters['currentUserUuid'] ?? null);
        $this->applyCompanyFilter($qb, filter_var($filters['companyFilter'] ?? false, FILTER_VALIDATE_BOOLEAN), $filters['currentUserCompanyUuid'] ?? null);

        // Apply sorting
        $this->applySorting($qb, $filters['sortBy'] ?? 'createdAt', $filters['sortOrder'] ?? 'DESC');

        return $qb->getQuery()->getResult();
    }

    private function applySearchFilter($qb, ?string $search): void
    {
        if ($search) {
            $qb->andWhere('b.title LIKE :search OR b.description LIKE :search OR b.ownerName LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
    }

    private function applyCategoryFilter($qb, ?string $category): void
    {
        if ($category && $category !== 'all') {
            $qb->andWhere('b.category = :category')
               ->setParameter('category', $category);
        }
    }

    private function applyAvailabilityFilter($qb, bool $availableOnly): void
    {
        if ($availableOnly) {
            $qb->andWhere('b.quantity > :quantity')
               ->setParameter('quantity', 0);
        }
    }

    private function applyPriceRangeFilter($qb, ?float $priceMin, ?float $priceMax): void
    {
        if ($priceMin !== null) {
            $qb->andWhere('b.price >= :priceMin')
               ->setParameter('priceMin', $priceMin);
        }
        
        if ($priceMax !== null) {
            $qb->andWhere('b.price <= :priceMax')
               ->setParameter('priceMax', $priceMax);
        }
    }

    private function applyOwnerFilter($qb, $ownerUuid): void
    {
        if ($ownerUuid) {
            $qb->andWhere('b.ownerUuid = :ownerUuid')
               ->setParameter('ownerUuid', $ownerUuid->toRfc4122());
        }
    }

    private function applyExcludeOwnFilter($qb, bool $excludeOwn, ?string $currentUserUuid): void
    {
        if ($excludeOwn && $currentUserUuid) {
            $qb->andWhere('b.ownerUuid != :currentUserUuid')
               ->setParameter('currentUserUuid', $currentUserUuid, 'uuid');
        }
    }

    private function applyCompanyFilter($qb, bool $companyFilter, ?string $currentUserCompanyUuid): void
    {
        if ($companyFilter && $currentUserCompanyUuid) {
            $qb->andWhere('b.companyUuid = :companyUuid')
               ->setParameter('companyUuid', $currentUserCompanyUuid);
        }
    }

    private function applySorting($qb, string $sortBy, string $sortOrder): void
    {
        $sortField = match ($sortBy) {
            'title' => 'b.title',
            'price' => 'b.price',
            'quantity' => 'b.quantity',
            default => 'b.createdAt'
        };

        $qb->orderBy($sortField, $sortOrder);
    }

    /**
     * Get books statistics for owner
     */
    public function getOwnerStatistics(Uuid $ownerUuid): array
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.id) as totalBooks')
            ->addSelect('SUM(b.quantity) as totalQuantity')
            ->addSelect('SUM(b.price * b.quantity) as totalValue')
            ->addSelect('SUM(CASE WHEN b.quantity > 0 THEN 1 ELSE 0 END) as availableBooks')
            ->where('b.ownerUuid = :ownerUuid')
            ->setParameter('ownerUuid', $ownerUuid->toRfc4122());

        $result = $qb->getQuery()->getSingleResult();

        return [
            'totalBooks' => (int) $result['totalBooks'],
            'totalQuantity' => (int) $result['totalQuantity'],
            'totalValue' => (float) $result['totalValue'],
            'availableBooks' => (int) $result['availableBooks'],
        ];
    }

    /**
     * Get platform statistics
     */
    public function getPlatformStatistics(): array
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.id) as totalBooks')
            ->addSelect('SUM(b.quantity) as totalQuantity')
            ->addSelect('SUM(b.price * b.quantity) as totalValue')
            ->addSelect('SUM(CASE WHEN b.quantity > 0 THEN 1 ELSE 0 END) as availableBooks')
            ->addSelect('COUNT(DISTINCT b.ownerUuid) as totalOwners');

        $result = $qb->getQuery()->getSingleResult();

        return [
            'totalBooks' => (int) $result['totalBooks'],
            'totalQuantity' => (int) $result['totalQuantity'],
            'totalValue' => (float) $result['totalValue'],
            'availableBooks' => (int) $result['availableBooks'],
            'totalOwners' => (int) $result['totalOwners'],
        ];
    }

    /**
     * Find recently added books
     */
    public function findRecentlyAdded(int $limit = 10): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.quantity > :quantity')
            ->setParameter('quantity', 0)
            ->orderBy('b.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find books by price range
     */
    public function findByPriceRange(float $minPrice, float $maxPrice): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.price >= :minPrice')
            ->andWhere('b.price <= :maxPrice')
            ->andWhere('b.quantity > :quantity')
            ->setParameter('minPrice', $minPrice)
            ->setParameter('maxPrice', $maxPrice)
            ->setParameter('quantity', 0)
            ->orderBy('b.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Save book
     */
    public function save(Book $book): void
    {
        $this->getEntityManager()->persist($book);
        $this->getEntityManager()->flush();
    }

    /**
     * Delete book
     */
    public function delete(Book $book): void
    {
        $this->getEntityManager()->remove($book);
        $this->getEntityManager()->flush();
    }

    /**
     * Find one by UUID
     */
    public function findOneByUuid(Uuid $uuid): ?Book
    {
        return $this->createQueryBuilder('b')
            ->where('b.id = :uuid')
            ->setParameter('uuid', $uuid->toRfc4122())
            ->getQuery()
            ->getOneOrNullResult();
    }
}
