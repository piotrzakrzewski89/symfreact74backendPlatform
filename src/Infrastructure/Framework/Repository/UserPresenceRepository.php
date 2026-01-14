<?php

declare(strict_types=1);

namespace App\Infrastructure\Framework\Repository;

use App\Domain\Entity\UserPresence;
use App\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<UserPresence>
 */
class UserPresenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPresence::class);
    }

    public function save(UserPresence $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserPresence $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return UserPresence[]
     */
    public function findOnlineUsers(): array
    {
        $fiveMinutesAgo = new \DateTimeImmutable('-5 minutes');
        
        return $this->createQueryBuilder('up')
            ->andWhere('up.status = :status')
            ->andWhere('up.lastSeen > :time')
            ->setParameter('status', 'online')
            ->setParameter('time', $fiveMinutesAgo)
            ->orderBy('up.lastSeen', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser(User $user): ?UserPresence
    {
        return $this->createQueryBuilder('up')
            ->andWhere('up.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return UserPresence[]
     */
    public function findByChatRoom(string $roomId): array
    {
        return $this->createQueryBuilder('up')
            ->andWhere('up.currentChatRoom = :roomId')
            ->andWhere('up.status != :offlineStatus')
            ->setParameter('roomId', $roomId)
            ->setParameter('offlineStatus', 'offline')
            ->orderBy('up.lastSeen', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
