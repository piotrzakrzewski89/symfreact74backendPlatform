<?php

declare(strict_types=1);

namespace App\Application\DTO\Chat;

use App\Application\DTO\Chat\UserPresenceResponse;
use App\Application\DTO\Chat\MessageResponse;
use Symfony\Component\Uid\Ulid;

final readonly class ConversationResponse
{
    public function __construct(
        public string $id,
        public UserPresenceResponse $otherUser,
        public ?MessageResponse $lastMessage = null,
        public int $unreadCount = 0,
        public \DateTimeImmutable $createdAt
    ) {
    }
}
