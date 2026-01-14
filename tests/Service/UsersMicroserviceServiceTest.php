<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Application\Service\KeycloakTokenService;
use App\Application\Service\UsersMicroserviceService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class UsersMicroserviceServiceTest extends TestCase
{
    public function testGetAllUsersReturnsUserArray(): void
    {
        $mockUsers = [
            [
                'id' => 1,
                'email' => 'test@test.com',
                'firstName' => 'Test',
                'lastName' => 'User',
                'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            ],
        ];

        $httpClient = new MockHttpClient([
            new MockResponse(json_encode(['access_token' => 'admin_token', 'expires_in' => 300])),
            new MockResponse(json_encode($mockUsers)),
        ]);

        $keycloakService = $this->createMock(KeycloakTokenService::class);
        $keycloakService->method('getAdminToken')->willReturn('admin_token');

        $service = new UsersMicroserviceService(
            $httpClient,
            'http://localhost:8082/api',
            $keycloakService
        );

        $users = $service->getAllUsers();

        $this->assertIsArray($users);
        $this->assertCount(1, $users);
        $this->assertEquals('test@test.com', $users[0]['email']);
    }

    public function testGetAllUsersThrowsExceptionOnFailure(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
        ]);

        $keycloakService = $this->createMock(KeycloakTokenService::class);
        $keycloakService->method('getAdminToken')->willReturn('admin_token');

        $service = new UsersMicroserviceService(
            $httpClient,
            'http://localhost:8082/api',
            $keycloakService
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch users from Users microservice');

        $service->getAllUsers();
    }

    public function testGetUserByEmailReturnsCorrectUser(): void
    {
        $mockUsers = [
            ['email' => 'user1@test.com', 'firstName' => 'User1'],
            ['email' => 'user2@test.com', 'firstName' => 'User2'],
        ];

        $httpClient = new MockHttpClient([
            new MockResponse(json_encode($mockUsers)),
        ]);

        $keycloakService = $this->createMock(KeycloakTokenService::class);
        $keycloakService->method('getAdminToken')->willReturn('admin_token');

        $service = new UsersMicroserviceService(
            $httpClient,
            'http://localhost:8082/api',
            $keycloakService
        );

        $user = $service->getUserByEmail('user2@test.com');

        $this->assertNotNull($user);
        $this->assertEquals('User2', $user['firstName']);
    }

    public function testGetUserByEmailReturnsNullWhenNotFound(): void
    {
        $mockUsers = [
            ['email' => 'user1@test.com', 'firstName' => 'User1'],
        ];

        $httpClient = new MockHttpClient([
            new MockResponse(json_encode($mockUsers)),
        ]);

        $keycloakService = $this->createMock(KeycloakTokenService::class);
        $keycloakService->method('getAdminToken')->willReturn('admin_token');

        $service = new UsersMicroserviceService(
            $httpClient,
            'http://localhost:8082/api',
            $keycloakService
        );

        $user = $service->getUserByEmail('nonexistent@test.com');

        $this->assertNull($user);
    }
}
