<?php

declare(strict_types=1);

namespace App\Infrastructure\Framework\Repository;

use App\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return User[]
     */
    public function findByCompany(string $companyId): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.company', 'c')
            ->andWhere('c.id = :companyId')
            ->setParameter('companyId', $companyId)
            ->orderBy('u.firstName', 'ASC')
            ->addOrderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return User[]
     */
    public function findActiveUsers(): array
    {
        $dql = 'SELECT u FROM App\Domain\Entity\User u ORDER BY u.firstName ASC, u.lastName ASC';
        return $this->getEntityManager()->createQuery($dql)->getResult();
    }
}
