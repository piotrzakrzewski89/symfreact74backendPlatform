<?php

declare(strict_types=1);

namespace App\Application\DTO\Chat;

use Symfony\Component\Uid\Uuid;

final readonly class UserPresenceResponse
{
    public function __construct(
        public string $id,
        public string $firstName,
        public string $lastName,
        public string $fullName,
        public ?string $avatar,
        public string $status, // online, offline, away, busy
        public \DateTimeImmutable $lastSeen,
        public ?string $currentChatRoom = null
    ) {
    }

    public static function fromEntity(\App\Domain\Entity\User $user, ?\App\Domain\Entity\UserPresence $presence = null): self
    {
        return new self(
            id: $user->getId()->toString(),
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            fullName: $user->getFirstName() . ' ' . $user->getLastName(),
            avatar: $user->getAvatar(),
            status: $presence?->getStatus() ?? 'offline',
            lastSeen: $presence?->getLastSeen() ?? new \DateTimeImmutable('-1 year'),
            currentChatRoom: $presence?->getCurrentChatRoom()
        );
    }
    
    public function getId(): string
    {
        return $this->id;
    }
}
