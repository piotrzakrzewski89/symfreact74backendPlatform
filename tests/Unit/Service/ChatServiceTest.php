<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Application\DTO\Chat\SendMessageRequest;
use App\Application\Service\ChatService;
use App\Application\Service\MercureService;
use App\Domain\Entity\User;
use App\Infrastructure\Framework\Repository\ChatMessageRepository;
use App\Infrastructure\Framework\Repository\ChatRoomRepository;
use App\Infrastructure\Framework\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class ChatServiceTest extends TestCase
{
    private ChatService $chatService;
    private ChatMessageRepository $messageRepository;
    private ChatRoomRepository $roomRepository;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->messageRepository = $this->createMock(ChatMessageRepository::class);
        $this->roomRepository = $this->createMock(ChatRoomRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->chatService = new ChatService(
            $this->roomRepository,
            $this->messageRepository,
            $this->userRepository,
            $this->entityManager,
            $this->createMock(MercureService::class)
        );
    }

    public function testSendMessageWithValidData(): void
    {
        // Arrange
        $senderId = new Ulid();
        $sender = $this->createMockUser($senderId);
        
        $recipientId = new Ulid();
        $recipient = $this->createMockUser($recipientId);
        
        $request = new SendMessageRequest(
            recipientId: $recipientId->toBase32(),
            content: 'Hello world!',
            type: 'text'
        );

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($senderId)
            ->willReturn($sender);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($recipientId)
            ->willReturn($recipient);

        // Act & Assert
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->chatService->sendMessage($senderId, $request);

        $this->assertSame('Hello world!', $result->content);
        $this->assertSame('text', $result->type);
        $this->assertTrue($result->isOwn);
    }

    public function testSendMessageWithInvalidRecipient(): void
    {
        // Arrange
        $senderId = new Ulid();
        $sender = $this->createMockUser($senderId);
        
        $request = new SendMessageRequest(
            recipientId: 'invalid-ulid',
            content: 'Hello world!',
            type: 'text'
        );

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($senderId)
            ->willReturn($sender);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($this->anything())
            ->willReturn(null);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Recipient not found');

        $this->chatService->sendMessage($senderId, $request);
    }

    public function testSendMessageWithEmptyContent(): void
    {
        // Arrange
        $senderId = new Ulid();
        $request = new SendMessageRequest(
            recipientId: new Ulid()->toBase32(),
            content: '',
            type: 'text'
        );

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);

        $this->chatService->sendMessage($senderId, $request);
    }

    private function createMockUser(Ulid $id): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        $user->method('getFirstName')->willReturn('John');
        $user->method('getLastName')->willReturn('Doe');
        
        return $user;
    }
}
