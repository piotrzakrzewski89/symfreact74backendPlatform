<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\Chat\UserPresenceResponse;
use App\Application\Service\MercureService;
use App\Domain\Entity\User;
use App\Domain\Entity\UserPresence;
use App\Infrastructure\Framework\Repository\UserPresenceRepository;
use App\Infrastructure\Framework\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class UserPresenceService
{
    public function __construct(
        private UserPresenceRepository $userPresenceRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private MercureService $mercureService
    ) {
    }

    public function updateUserPresence(Uuid $userId, string $status, ?string $currentChatRoom = null): UserPresenceResponse
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        $presence = $this->userPresenceRepository->findByUser($user);
        
        if (!$presence) {
            $presence = new UserPresence();
            $presence->setUser($user);
        }

        $presence->setStatus($status);
        $presence->setLastSeen(new \DateTimeImmutable());
        $presence->setCurrentChatRoom($currentChatRoom);
        $presence->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($presence);
        $this->entityManager->flush();

        // Publikuj zmianę statusu do Mercure
        $this->mercureService->publishUserPresence($presence);

        return UserPresenceResponse::fromEntity($user, $presence);
    }

    public function getOnlineUsers(): array
    {
        $onlinePresences = $this->userPresenceRepository->findOnlineUsers();
        $responses = [];

        foreach ($onlinePresences as $presence) {
            $user = $presence->getUser();
            $responses[] = UserPresenceResponse::fromEntity($user, $presence);
        }

        return $responses;
    }

    public function getUserPresence(Uuid $userId): ?UserPresenceResponse
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return null;
        }

        $presence = $this->userPresenceRepository->findByUser($user);
        
        return UserPresenceResponse::fromEntity($user, $presence);
    }

    public function markUserAsOffline(Uuid $userId): void
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return;
        }

        $presence = $this->userPresenceRepository->findByUser($user);
        if ($presence) {
            $presence->setStatus('offline');
            $presence->setLastSeen(new \DateTimeImmutable());
            $presence->setCurrentChatRoom(null);
            $presence->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->flush();
        }
    }

    public function markUserAsOnline(Uuid $userId): void
    {
        $this->updateUserPresence($userId, 'online');
    }

    public function markUserAsAway(Uuid $userId): void
    {
        $this->updateUserPresence($userId, 'away');
    }

    public function markUserAsBusy(Uuid $userId): void
    {
        $this->updateUserPresence($userId, 'busy');
    }

    /**
     * Cleanup offline users who haven't been seen for more than 30 minutes
     */
    public function cleanupOfflineUsers(): int
    {
        $threshold = new \DateTimeImmutable('-30 minutes');
        $offlinePresences = $this->userPresenceRepository
            ->createQueryBuilder('up')
            ->andWhere('up.lastSeen < :threshold')
            ->andWhere('up.status != :offline')
            ->setParameter('threshold', $threshold)
            ->setParameter('offline', 'offline')
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($offlinePresences as $presence) {
            $presence->setStatus('offline');
            $presence->setCurrentChatRoom(null);
            $presence->setUpdatedAt(new \DateTimeImmutable());
            $count++;
        }

        if ($count > 0) {
            $this->entityManager->flush();
        }

        return $count;
    }
}
