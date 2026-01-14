<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\Chat\MessageResponse;
use App\Application\DTO\Chat\UserPresenceResponse;
use App\Domain\Entity\ChatMessage;
use App\Domain\Entity\UserPresence;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Uid\Ulid;

final readonly class MercureService
{
    public function __construct(
        private HubInterface $hub
    ) {
    }

    public function publishNewMessage(ChatMessage $message): void
    {
        // Publikuj do nadawcy i odbiorcy
        
        try {
            $recipientId = $message->getRecipientId();
            $senderId = $message->getSenderId();
            
            // Struktura zgodna z oczekiwaniami frontendu (ChatWidget.tsx linia 82)
            $messageData = [
                'type' => 'new_message',
                'message' => [
                    'id' => $message->getId()->toBase32(),
                    'roomId' => $recipientId->toString(), // Tymczasowo używamy recipientId jako roomId
                    'senderId' => $senderId->toString(),
                    'senderName' => 'User',
                    'senderAvatar' => '',
                    'content' => $message->getContent(),
                    'type' => $message->getMessageType() ?? 'text',
                    'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
                    'editedAt' => null,
                    'isOwn' => false
                ]
            ];

            // Publikuj do odbiorcy
            $updateRecipient = new Update(
                topics: ["chat/user/{$recipientId->toString()}"],
                data: json_encode($messageData)
            );
            $this->hub->publish($updateRecipient);

            // Publikuj do nadawcy (z isOwn = true)
            $messageData['message']['isOwn'] = true;
            $updateSender = new Update(
                topics: ["chat/user/{$senderId->toString()}"],
                data: json_encode($messageData)
            );
            $this->hub->publish($updateSender);

        } catch (\Exception $e) {
            // Loguj błąd, ale nie blokuj zapisywania wiadomości
            error_log("Mercure publish error: " . $e->getMessage());
        }
    }

    public function publishUserPresence(UserPresence $presence): void
    {
        $user = $presence->getUser();
        $userId = $user->getId()->toBase32();
        
        $presenceData = UserPresenceResponse::fromEntity($user, $presence);
        
        // Publikuj status użytkownika do wszystkich
        $update = new Update(
            topics: ["user/presence/{$userId}"],
            data: json_encode([
                'type' => 'presence_update',
                'user' => $presenceData
            ])
        );

        $this->hub->publish($update);
    }

    public function publishUserPresenceUpdate(\Symfony\Component\Uid\Uuid $userId, array $presenceData): void
    {
        try {
            $update = new Update(
                topics: ["chat/user/{$userId->toString()}"],
                data: json_encode([
                    'type' => 'presence_update',
                    'user' => $presenceData
                ])
            );

            $this->hub->publish($update);
        } catch (\Exception $e) {
            // Ignoruj błędy - presence jest już zapisane w Redis
        }
    }

    public function publishTypingIndicator(\Symfony\Component\Uid\Uuid $senderId, \Symfony\Component\Uid\Uuid $recipientId, bool $isTyping): void
    {
        try {
            $update = new Update(
                topics: ["chat/user/{$recipientId->toString()}"],
                data: json_encode([
                    'type' => 'typing_indicator',
                    'userId' => $senderId->toString(),
                    'isTyping' => $isTyping,
                    'timestamp' => time()
                ])
            );

            $this->hub->publish($update);
        } catch (\Exception $e) {
            // Ignoruj błędy - typing indicator nie jest krytyczny
        }
    }

    public function publishUserJoinedRoom(Ulid $roomId, Ulid $userId, string $userName): void
    {
        $update = new Update(
            topics: ["chat/room/{$roomId->toBase32()}/users"],
            data: json_encode([
                'type' => 'user_joined',
                'userId' => $userId->toBase32(),
                'userName' => $userName,
                'roomId' => $roomId->toBase32(),
                'timestamp' => time()
            ])
        );

        $this->hub->publish($update);
    }

    public function publishUserLeftRoom(Ulid $roomId, Ulid $userId, string $userName): void
    {
        $update = new Update(
            topics: ["chat/room/{$roomId->toBase32()}/users"],
            data: json_encode([
                'type' => 'user_left',
                'userId' => $userId->toBase32(),
                'userName' => $userName,
                'roomId' => $roomId->toBase32(),
                'timestamp' => time()
            ])
        );

        $this->hub->publish($update);
    }

    public function publishMessagesRead(Ulid $roomId, Ulid $userId): void
    {
        $update = new Update(
            topics: ["chat/room/{$roomId->toBase32()}/read"],
            data: json_encode([
                'type' => 'messages_read',
                'userId' => $userId->toBase32(),
                'roomId' => $roomId->toBase32(),
                'timestamp' => time()
            ])
        );

        $this->hub->publish($update);
    }
}
