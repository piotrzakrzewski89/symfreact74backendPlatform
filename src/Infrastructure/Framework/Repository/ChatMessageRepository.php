<?php

declare(strict_types=1);

namespace App\Infrastructure\Framework\Repository;

use App\Domain\Entity\ChatMessage;
use App\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatMessage>
 */
class ChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMessage::class);
    }

    public function save(ChatMessage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ChatMessage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return ChatMessage[]
     */
    public function findByRoomOrderedByCreatedAt(string $roomId, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.room = :roomId')
            ->setParameter('roomId', $roomId)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ChatMessage[]
     */
    public function findConversation(User $user1, User $user2, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('(m.sender = :user1 AND m.recipient = :user2) OR (m.sender = :user2 AND m.recipient = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUnreadByUsers(User $user, User $otherUser): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.sender = :otherUser')
            ->andWhere('m.recipient = :user')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('otherUser', $otherUser)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return ChatMessage[]
     */
    public function findUnreadMessages(User $user, User $otherUser): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.sender = :otherUser')
            ->andWhere('m.recipient = :user')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('otherUser', $otherUser)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
