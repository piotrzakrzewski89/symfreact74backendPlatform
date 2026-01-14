<?php

declare(strict_types=1);

namespace App\Infrastructure\Framework\Repository;

use App\Domain\Entity\ChatRoom;
use App\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<ChatRoom>
 */
class ChatRoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatRoom::class);
    }

    public function save(ChatRoom $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ChatRoom $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return ChatRoom[]
     */
    public function findByUser(Ulid $userId): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.participants', 'p')
            ->andWhere('p.id = :userId')
            ->andWhere('r.isActive = :isActive')
            ->setParameter('userId', $userId->toBase32())
            ->setParameter('isActive', true)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find direct chat room between two users
     */
    public function findDirectChatRoom(User $user1, User $user2): ?ChatRoom
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.participants', 'p1')
            ->innerJoin('r.participants', 'p2')
            ->andWhere('r.type = :type')
            ->andWhere('p1.id = :userId1')
            ->andWhere('p2.id = :userId2')
            ->andWhere('r.isActive = :isActive')
            ->setParameter('type', 'direct')
            ->setParameter('userId1', $user1->getId()->toBase32())
            ->setParameter('userId2', $user2->getId()->toBase32())
            ->setParameter('isActive', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
