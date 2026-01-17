<?php

namespace App\Domain\Repository;

use App\Domain\Entity\ShippingAddress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ShippingAddress>
 */
class ShippingAddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShippingAddress::class);
    }

    public function save(ShippingAddress $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ShippingAddress $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find addresses by user UUID
     */
    public function findByUserUuid(Uuid $userUuid): array
    {
        return $this->createQueryBuilder('sa')
            ->where('sa.userUuid = :userUuid')
            ->setParameter('userUuid', $userUuid->toRfc4122())
            ->orderBy('sa.isDefault', 'DESC')
            ->addOrderBy('sa.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find default address for user
     */
    public function findDefaultByUserUuid(Uuid $userUuid): ?ShippingAddress
    {
        return $this->createQueryBuilder('sa')
            ->where('sa.userUuid = :userUuid')
            ->andWhere('sa.isDefault = :isDefault')
            ->setParameter('userUuid', $userUuid->toRfc4122())
            ->setParameter('isDefault', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Set address as default (unset others)
     */
    public function setAsDefault(Uuid $addressId): void
    {
        // Unset all other addresses for this user
        $this->createQueryBuilder('sa')
            ->update()
            ->set('sa.isDefault', 'false')
            ->where('sa.id != :addressId')
            ->setParameter('addressId', $addressId->toRfc4122())
            ->getQuery()
            ->execute();

        // Set this address as default
        $this->createQueryBuilder('sa')
            ->update()
            ->set('sa.isDefault', 'true')
            ->where('sa.id = :addressId')
            ->setParameter('addressId', $addressId->toRfc4122())
            ->getQuery()
            ->execute();
    }
}
