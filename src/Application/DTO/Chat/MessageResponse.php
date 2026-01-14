<?php

declare(strict_types=1);

namespace App\Application\DTO\Chat;

use Symfony\Component\Uid\Ulid;

final readonly class MessageResponse
{
    public function __construct(
        public Ulid $id,
        public Ulid $roomId,
        public Ulid $senderId,
        public string $senderName,
        public string $senderAvatar,
        public string $content,
        public string $type,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $editedAt = null,
        public bool $isOwn = false
    ) {
    }

    public static function fromEntity(\App\Domain\Entity\ChatMessage $message, bool $isOwn = false): self
    {
        $sender = $message->getSender();
        
        return new self(
            id: $message->getId(),
            roomId: $message->getRoom()->getId(),
            senderId: $sender->getId(),
            senderName: $sender->getFirstName() . ' ' . $sender->getLastName(),
            senderAvatar: $sender->getAvatar() ?? '',
            content: $message->getContent(),
            type: $message->getMessageType(),
            createdAt: $message->getCreatedAt(),
            editedAt: $message->getEditedAt(),
            isOwn: $isOwn
        );
    }
}
