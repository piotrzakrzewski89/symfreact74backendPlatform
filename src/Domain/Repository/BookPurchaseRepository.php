<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\BookPurchase;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<BookPurchase>
 */
class BookPurchaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookPurchase::class);
    }

    /**
     * Find purchases by buyer UUID
     */
    public function findByBuyer(Uuid $buyerUuid): array
    {
        return $this->createQueryBuilder('bp')
            ->leftJoin('bp.book', 'b')
            ->addSelect('b')
            ->where('bp.buyerUuid = :buyerUuid')
            ->setParameter('buyerUuid', $buyerUuid->toRfc4122())
            ->orderBy('bp.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find purchases by book UUID
     */
    public function findByBook(Uuid $bookUuid): array
    {
        return $this->createQueryBuilder('bp')
            ->leftJoin('bp.book', 'b')
            ->addSelect('b')
            ->where('b.id = :bookUuid')
            ->setParameter('bookUuid', $bookUuid->toRfc4122())
            ->orderBy('bp.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find purchases by seller (book owner) UUID
     */
    public function findBySeller(Uuid $sellerUuid): array
    {
        return $this->createQueryBuilder('bp')
            ->leftJoin('bp.book', 'b')
            ->addSelect('b')
            ->where('b.ownerUuid = :sellerUuid')
            ->setParameter('sellerUuid', $sellerUuid->toRfc4122())
            ->orderBy('bp.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find purchases by status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('bp')
            ->leftJoin('bp.book', 'b')
            ->addSelect('b')
            ->where('bp.status = :status')
            ->setParameter('status', $status)
            ->orderBy('bp.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find purchases with filters
     */
    public function findWithFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('bp')
            ->leftJoin('bp.book', 'b')
            ->addSelect('b');

        // Buyer filter
        if (!empty($filters['buyerUuid'])) {
            $qb->andWhere('bp.buyerUuid = :buyerUuid')
               ->setParameter('buyerUuid', $filters['buyerUuid']->toRfc4122());
        }

        // Seller filter
        if (!empty($filters['sellerUuid'])) {
            $qb->andWhere('b.ownerUuid = :sellerUuid')
               ->setParameter('sellerUuid', $filters['sellerUuid']->toRfc4122());
        }

        // Book filter
        if (!empty($filters['bookUuid'])) {
            $qb->andWhere('b.id = :bookUuid')
               ->setParameter('bookUuid', $filters['bookUuid']->toRfc4122());
        }

        // Status filter
        if (!empty($filters['status'])) {
            $qb->andWhere('bp.status = :status')
               ->setParameter('status', $filters['status']);
        }

        // Date range filter
        if (!empty($filters['dateFrom'])) {
            $qb->andWhere('bp.createdAt >= :dateFrom')
               ->setParameter('dateFrom', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $qb->andWhere('bp.createdAt <= :dateTo')
               ->setParameter('dateTo', $filters['dateTo']);
        }

        // Price range filter
        if (isset($filters['priceMin'])) {
            $qb->andWhere('bp.totalPrice >= :priceMin')
               ->setParameter('priceMin', $filters['priceMin']);
        }

        if (isset($filters['priceMax'])) {
            $qb->andWhere('bp.totalPrice <= :priceMax')
               ->setParameter('priceMax', $filters['priceMax']);
        }

        // Sorting
        $sortBy = $filters['sortBy'] ?? 'createdAt';
        $sortOrder = $filters['sortOrder'] ?? 'DESC';

        switch ($sortBy) {
            case 'totalPrice':
                $qb->orderBy('bp.totalPrice', $sortOrder);
                break;
            case 'quantity':
                $qb->orderBy('bp.quantity', $sortOrder);
                break;
            case 'status':
                $qb->orderBy('bp.status', $sortOrder);
                break;
            case 'createdAt':
            default:
                $qb->orderBy('bp.createdAt', $sortOrder);
                break;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get buyer statistics
     */
    public function getBuyerStatistics(Uuid $buyerUuid): array
    {
        $qb = $this->createQueryBuilder('bp')
            ->select('COUNT(bp.id) as totalPurchases')
            ->addSelect('SUM(bp.quantity) as totalBooks')
            ->addSelect('SUM(bp.price * bp.quantity) as totalSpent')
            ->addSelect('SUM(CASE WHEN bp.status = :completed THEN 1 ELSE 0 END) as completedPurchases')
            ->addSelect('SUM(CASE WHEN bp.status = :pending THEN 1 ELSE 0 END) as pendingPurchases')
            ->where('bp.buyerUuid = :buyerUuid')
            ->setParameter('buyerUuid', $buyerUuid->toRfc4122())
            ->setParameter('completed', BookPurchase::STATUS_COMPLETED)
            ->setParameter('pending', BookPurchase::STATUS_PENDING);

        $result = $qb->getQuery()->getSingleResult();

        return [
            'totalPurchases' => (int) $result['totalPurchases'],
            'totalBooks' => (int) $result['totalBooks'],
            'totalSpent' => (float) $result['totalSpent'],
            'completedPurchases' => (int) $result['completedPurchases'],
            'pendingPurchases' => (int) $result['pendingPurchases'],
        ];
    }

    /**
     * Get seller statistics
     */
    public function getSellerStatistics(Uuid $sellerUuid): array
    {
        $qb = $this->createQueryBuilder('bp')
            ->leftJoin('bp.book', 'b')
            ->select('COUNT(bp.id) as totalSales')
            ->addSelect('SUM(bp.quantity) as totalBooksSold')
            ->addSelect('SUM(bp.totalPrice) as totalRevenue')
            ->addSelect('SUM(CASE WHEN bp.status = :completed THEN 1 ELSE 0 END) as completedSales')
            ->addSelect('SUM(CASE WHEN bp.status = :pending THEN 1 ELSE 0 END) as pendingSales')
            ->where('b.ownerUuid = :sellerUuid')
            ->setParameter('sellerUuid', $sellerUuid->toRfc4122())
            ->setParameter('completed', BookPurchase::STATUS_COMPLETED)
            ->setParameter('pending', BookPurchase::STATUS_PENDING);

        $result = $qb->getQuery()->getSingleResult();

        return [
            'totalSales' => (int) $result['totalSales'],
            'totalBooksSold' => (int) $result['totalBooksSold'],
            'totalRevenue' => (float) $result['totalRevenue'],
            'completedSales' => (int) $result['completedSales'],
            'pendingSales' => (int) $result['pendingSales'],
        ];
    }

    /**
     * Get platform purchase statistics
     */
    public function getPlatformStatistics(): array
    {
        $qb = $this->createQueryBuilder('bp')
            ->select('COUNT(bp.id) as totalPurchases')
            ->addSelect('SUM(bp.quantity) as totalBooks')
            ->addSelect('SUM(bp.totalPrice) as totalRevenue')
            ->addSelect('SUM(CASE WHEN bp.status = :completed THEN 1 ELSE 0 END) as completedPurchases')
            ->addSelect('SUM(CASE WHEN bp.status = :pending THEN 1 ELSE 0 END) as pendingPurchases')
            ->addSelect('SUM(CASE WHEN bp.status = :cancelled THEN 1 ELSE 0 END) as cancelledPurchases')
            ->addSelect('COUNT(DISTINCT bp.buyerUuid) as totalBuyers')
            ->setParameter('completed', BookPurchase::STATUS_COMPLETED)
            ->setParameter('pending', BookPurchase::STATUS_PENDING)
            ->setParameter('cancelled', BookPurchase::STATUS_CANCELLED);

        $result = $qb->getQuery()->getSingleResult();

        return [
            'totalPurchases' => (int) $result['totalPurchases'],
            'totalBooks' => (int) $result['totalBooks'],
            'totalRevenue' => (float) $result['totalRevenue'],
            'completedPurchases' => (int) $result['completedPurchases'],
            'pendingPurchases' => (int) $result['pendingPurchases'],
            'cancelledPurchases' => (int) $result['cancelledPurchases'],
            'totalBuyers' => (int) $result['totalBuyers'],
        ];
    }

    /**
     * Find recent purchases
     */
    public function findRecentPurchases(int $limit = 10): array
    {
        return $this->createQueryBuilder('bp')
            ->leftJoin('bp.book', 'b')
            ->addSelect('b')
            ->where('bp.status = :status')
            ->setParameter('status', BookPurchase::STATUS_COMPLETED)
            ->orderBy('bp.completedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pending purchases
     */
    public function findPendingPurchases(): array
    {
        return $this->createQueryBuilder('bp')
            ->leftJoin('bp.book', 'b')
            ->addSelect('b')
            ->where('bp.status = :status')
            ->setParameter('status', BookPurchase::STATUS_PENDING)
            ->orderBy('bp.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Save purchase
     */
    public function save(BookPurchase $purchase): void
    {
        $this->getEntityManager()->persist($purchase);
        $this->getEntityManager()->flush();
    }

    /**
     * Delete purchase
     */
    public function delete(BookPurchase $purchase): void
    {
        $this->getEntityManager()->remove($purchase);
        $this->getEntityManager()->flush();
    }

    /**
     * Find one by UUID
     */
    public function findOneByUuid(Uuid $uuid): ?BookPurchase
    {
        return $this->createQueryBuilder('bp')
            ->leftJoin('bp.book', 'b')
            ->addSelect('b')
            ->where('bp.id = :uuid')
            ->setParameter('uuid', $uuid->toRfc4122())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find purchases by transaction ID
     */
    public function findByTransactionId(string $transactionId): ?BookPurchase
    {
        return $this->createQueryBuilder('bp')
            ->leftJoin('bp.book', 'b')
            ->addSelect('b')
            ->where('bp.transactionId = :transactionId')
            ->setParameter('transactionId', $transactionId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
