<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\Chat\MessageResponse;
use App\Application\DTO\Chat\SendMessageRequest;
use App\Domain\Entity\ChatMessage;
use App\Infrastructure\Framework\Repository\ChatMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class ChatServiceSimplified
{
    public function __construct(
        private ChatMessageRepository $chatMessageRepository,
        private EntityManagerInterface $entityManager,
        private MercureService $mercureService
    ) {
    }

    public function sendMessage(Uuid $senderId, SendMessageRequest $request): MessageResponse
    {
        $message = new ChatMessage();
        $message->setSenderId($senderId);
        $message->setRecipientId(new Uuid($request->recipientId));
        $message->setContent($request->content);
        $message->setMessageType($request->type);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $this->mercureService->publishNewMessage($message);

        return MessageResponse::fromEntitySimplified($message, true);
    }

    public function getMessages(Uuid $userId, Uuid $otherUserId): array
    {
        $messages = $this->chatMessageRepository->findConversationByIds($userId, $otherUserId, 50);
        
        return array_map(
            fn(ChatMessage $msg) => MessageResponse::fromEntitySimplified($msg, $msg->getSenderId() === $userId),
            $messages
        );
    }

    public function getConversations(Uuid $userId): array
    {
        // Zwróć pustą tablicę - konwersacje będą pobierane przez frontend bezpośrednio
        return [];
    }

    public function markMessagesAsRead(Uuid $userId, Uuid $otherUserId): void
    {
        $unreadMessages = $this->chatMessageRepository->findUnreadMessagesByIds($userId, $otherUserId);
        
        foreach ($unreadMessages as $message) {
            $message->setReadAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();
    }
}
