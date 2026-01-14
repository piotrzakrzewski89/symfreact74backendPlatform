<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Application\DTO\Chat\UserPresenceResponse;
use App\Application\Service\UserPresenceService;
use App\Application\Service\MercureService;
use App\Domain\Entity\User;
use App\Domain\Entity\UserPresence;
use App\Infrastructure\Framework\Repository\UserPresenceRepository;
use App\Infrastructure\Framework\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

final class UserPresenceServiceTest extends TestCase
{
    private UserPresenceService $userPresenceService;
    private UserPresenceRepository $presenceRepository;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->presenceRepository = $this->createMock(UserPresenceRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->userPresenceService = new UserPresenceService(
            $this->presenceRepository,
            $this->userRepository,
            $this->entityManager,
            $this->createMock(MercureService::class)
        );
    }

    public function testUpdateUserPresenceWithValidData(): void
    {
        // Arrange
        $userId = new Ulid();
        $user = $this->createMockUser($userId);
        
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Act
        $result = $this->userPresenceService->updateUserPresence($userId, 'online');

        // Assert
        $this->assertSame('online', $result->status);
        $this->assertSame($userId->toBase32(), $result->id->toBase32());
    }

    public function testUpdateUserPresenceWithInvalidUser(): void
    {
        // Arrange
        $userId = new Ulid();
        
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User not found');

        $this->userPresenceService->updateUserPresence($userId, 'online');
    }

    public function testGetOnlineUsers(): void
    {
        // Arrange
        $presence1 = $this->createMockPresence('online');
        $presence2 = $this->createMockPresence('online');
        
        $this->presenceRepository
            ->expects($this->once())
            ->method('findOnlineUsers')
            ->willReturn([$presence1, $presence2]);

        // Act
        $result = $this->userPresenceService->getOnlineUsers();

        // Assert
        $this->assertCount(2, $result);
        $this->assertSame('online', $result[0]->status);
        $this->assertSame('online', $result[1]->status);
    }

    private function createMockUser(Ulid $id): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        $user->method('getFirstName')->willReturn('John');
        $user->method('getLastName')->willReturn('Doe');
        $user->method('getAvatar')->willReturn('avatar.jpg');
        
        return $user;
    }

    private function createMockPresence(string $status): UserPresence
    {
        $presence = $this->createMock(UserPresence::class);
        $user = $this->createMockUser(new Ulid());
        
        $presence->method('getUser')->willReturn($user);
        $presence->method('getStatus')->willReturn($status);
        $presence->method('getLastSeen')->willReturn(new \DateTimeImmutable());
        
        return $presence;
    }
}
