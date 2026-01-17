<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\User;
use Symfony\Component\Uid\Uuid;
use Predis\Client as RedisClient;

final class UserPresenceRedisService
{
    private const ONLINE_PREFIX = 'user:online:';
    private const PRESENCE_PREFIX = 'user:presence:';
    private const ONLINE_TTL = 60; // 1 min

    public function __construct(
        private RedisClient $redis,
        private MercureService $mercureService
    ) {
    }

    public function markUserAsOnline(Uuid $userId): void
    {
        $key = self::ONLINE_PREFIX . $userId->toString();
        $this->redis->setex($key, self::ONLINE_TTL, '1');
        
        // Aktualizuj dane obecności
        $this->updateUserPresence($userId, [
            'status' => 'online',
            'lastSeen' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'currentChatRoom' => null
        ]);
    }

    public function markUserAsOffline(Uuid $userId): void
    {
        $key = self::ONLINE_PREFIX . $userId->toString();
        $this->redis->del($key);
        
        $this->updateUserPresence($userId, [
            'status' => 'offline',
            'lastSeen' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'currentChatRoom' => null
        ]);
    }

    public function markUserAsAway(Uuid $userId): void
    {
        $this->updateUserPresence($userId, [
            'status' => 'away',
            'lastSeen' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'currentChatRoom' => null
        ]);
    }

    public function markUserAsBusy(Uuid $userId): void
    {
        $this->updateUserPresence($userId, [
            'status' => 'busy',
            'lastSeen' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'currentChatRoom' => null
        ]);
    }

    public function updateUserPresence(Uuid $userId, array $data): void
    {
        $key = self::PRESENCE_PREFIX . $userId->toString();
        $currentData = $this->getUserPresence($userId);
        
        $mergedData = array_merge($currentData, $data);
        $mergedData['id'] = $userId->toString();
        $this->redis->setex($key, self::ONLINE_TTL, json_encode($mergedData));
        
        // Publikuj zmianę statusu przez Mercure
        try {
            $this->mercureService->publishUserPresenceUpdate($userId, $mergedData);
        } catch (\Exception $e) {
            // Ignoruj błędy Mercure - presence jest już zapisane w Redis
        }
    }

    public function getUserPresence(Uuid $userId): array
    {
        $key = self::PRESENCE_PREFIX . $userId->toString();
        $data = $this->redis->get($key);
        
        if (!$data) {
            return [
                'status' => 'offline',
                'lastSeen' => (new \DateTimeImmutable('-1 year'))->format('Y-m-d H:i:s'),
                'currentChatRoom' => null
            ];
        }
        
        return json_decode($data, true);
    }

    public function getOnlineUsers(): array
    {
        $pattern = self::ONLINE_PREFIX . '*';
        $keys = $this->redis->keys($pattern);
        
        $onlineUsers = [];
        foreach ($keys as $key) {
            $userId = str_replace(self::ONLINE_PREFIX, '', $key);
            $presenceData = $this->getUserPresence(new Uuid($userId));
            
            $onlineUsers[] = [
                'id' => $userId,
                'status' => $presenceData['status'],
                'lastSeen' => $presenceData['lastSeen'],
                'currentChatRoom' => $presenceData['currentChatRoom']
            ];
        }
        
        return $onlineUsers;
    }

    public function isUserOnline(Uuid $userId): bool
    {
        $key = self::ONLINE_PREFIX . $userId->toString();
        return (bool) $this->redis->exists($key);
    }

    public function cleanupOfflineUsers(): int
    {
        // Redis automatycznie usuwa klucze po upływie TTL
        // Ta metoda może służyć do dodatkowego sprzątania
        return 0;
    }
}
