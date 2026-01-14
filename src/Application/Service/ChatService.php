<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\Chat\ConversationResponse;
use App\Application\DTO\Chat\MessageResponse;
use App\Application\DTO\Chat\SendMessageRequest;
use App\Application\DTO\Chat\UserPresenceResponse;
use App\Domain\Entity\ChatMessage;
use App\Infrastructure\Framework\Repository\ChatMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class ChatService
{
    public function __construct(
        private ChatMessageRepository $chatMessageRepository,
        private EntityManagerInterface $entityManager,
        private MercureService $mercureService
    ) {
    }

    public function sendMessage(Uuid $senderId, SendMessageRequest $request): MessageResponse
    {
        // Tworzymy i zapisujemy wiadomość do bazy
        $message = new ChatMessage();
        $message->setSenderId($senderId);
        $message->setRecipientId(new Uuid($request->recipientId));
        $message->setContent($request->content);
        $message->setMessageType($request->type);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Publikuj wiadomość do Mercure
        try {
            $this->mercureService->publishNewMessage($message);
        } catch (\Exception $e) {
            // Ignoruj błędy Mercure - wiadomość jest już zapisana
        }

        // Zwróć response z prawdziwymi danymi
        return new MessageResponse(
            id: $message->getId(),
            roomId: new \Symfony\Component\Uid\Ulid(), // Tymczasowo - do implementacji chat rooms
            senderId: new \Symfony\Component\Uid\Ulid(), // Konwersja UUID → Ulid
            senderName: 'User', // TODO: Pobrać z Users API
            senderAvatar: '',
            content: $message->getContent(),
            type: $message->getMessageType() ?? 'text',
            createdAt: $message->getCreatedAt(),
            editedAt: $message->getEditedAt(),
            isOwn: true
        );
    }

    public function getConversation(Uuid $userId, Uuid $otherUserId): ?ConversationResponse
    {
        // Tymczasowo zwracamy null - wymaga refaktoryzacji encji ChatMessage
        return null;
    }

    public function getConversations(Uuid $userId): array
    {
        // Pobierz wszystkie unikalne konwersacje użytkownika
        $dql = "SELECT m FROM App\Domain\Entity\ChatMessage m
                WHERE m.senderId = :userId OR m.recipientId = :userId
                ORDER BY m.createdAt DESC";
        
        $messages = $this->entityManager->createQuery($dql)
            ->setParameter('userId', $userId)
            ->getResult();
        
        // Grupuj wiadomości po drugim użytkowniku
        $conversations = [];
        $seenUsers = [];
        
        foreach ($messages as $message) {
            $otherUserId = $message->getSenderId()->toString() === $userId->toString() 
                ? $message->getRecipientId()->toString()
                : $message->getSenderId()->toString();
            
            // Pomiń jeśli już mamy konwersację z tym użytkownikiem
            if (isset($seenUsers[$otherUserId])) {
                continue;
            }
            
            $seenUsers[$otherUserId] = true;
            
            // Policz nieprzeczytane wiadomości
            $unreadCount = $this->entityManager->createQuery(
                "SELECT COUNT(m) FROM App\Domain\Entity\ChatMessage m
                 WHERE m.recipientId = :userId 
                 AND m.senderId = :otherUserId 
                 AND m.readAt IS NULL"
            )
            ->setParameter('userId', $userId)
            ->setParameter('otherUserId', $otherUserId)
            ->getSingleScalarResult();
            
            // Stwórz tymczasowy UserPresenceResponse - frontend pobierze pełne dane z Users API
            $otherUserPresence = new UserPresenceResponse(
                id: $otherUserId,
                firstName: '',
                lastName: '',
                fullName: 'User',
                avatar: null,
                status: 'offline',
                lastSeen: new \DateTimeImmutable(),
                currentChatRoom: null
            );
            
            $conversations[] = new ConversationResponse(
                id: $otherUserId,
                otherUser: $otherUserPresence,
                lastMessage: new MessageResponse(
                    id: $message->getId(),
                    roomId: new \Symfony\Component\Uid\Ulid(),
                    senderId: new \Symfony\Component\Uid\Ulid(),
                    senderName: 'User',
                    senderAvatar: '',
                    content: $message->getContent(),
                    type: $message->getMessageType() ?? 'text',
                    createdAt: $message->getCreatedAt(),
                    editedAt: $message->getEditedAt(),
                    isOwn: $message->getSenderId()->toString() === $userId->toString()
                ),
                unreadCount: (int)$unreadCount,
                createdAt: $message->getCreatedAt()
            );
        }
        
        return $conversations;
    }

    public function getMessages(Uuid $userId, Uuid $otherUserId): array
    {
        // Pobierz wiadomości między dwoma użytkownikami
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('m')
            ->from(ChatMessage::class, 'm')
            ->where('(m.senderId = :userId AND m.recipientId = :otherUserId)')
            ->orWhere('(m.senderId = :otherUserId AND m.recipientId = :userId)')
            ->setParameter('userId', $userId)
            ->setParameter('otherUserId', $otherUserId)
            ->orderBy('m.createdAt', 'ASC')
            ->setMaxResults(50);

        $messages = $qb->getQuery()->getResult();

        return array_map(function (ChatMessage $msg) use ($userId) {
            return new MessageResponse(
                id: $msg->getId(),
                roomId: new \Symfony\Component\Uid\Ulid(),
                senderId: new \Symfony\Component\Uid\Ulid(),
                senderName: 'User',
                senderAvatar: '',
                content: $msg->getContent(),
                type: $msg->getMessageType() ?? 'text',
                createdAt: $msg->getCreatedAt(),
                editedAt: $msg->getEditedAt(),
                isOwn: $msg->getSenderId()->toString() === $userId->toString()
            );
        }, $messages);
    }

    public function markMessagesAsRead(Uuid $userId, Uuid $otherUserId): void
    {
        // Tymczasowo nic nie robimy - wymaga refaktoryzacji
    }
}
