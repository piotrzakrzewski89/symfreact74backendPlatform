<?php

declare(strict_types=1);

namespace App\Application\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class UsersMicroserviceService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $usersApiUrl,
        private KeycloakTokenService $keycloakTokenService
    ) {
    }

    public function getAllUsers(): array
    {
        $token = $this->keycloakTokenService->getAdminToken();
        
        $response = $this->httpClient->request('GET', $this->usersApiUrl . '/user/active', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ]
        ]);
        
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Failed to fetch users from Users microservice');
        }
        
        return $response->toArray();
    }

    public function getUserByEmail(string $email): ?array
    {
        $users = $this->getAllUsers();
        
        foreach ($users as $user) {
            if ($user['email'] === $email) {
                return $user;
            }
        }
        
        return null;
    }

    public function getUserById(string $id): ?array
    {
        $response = $this->httpClient->request('GET', $this->usersApiUrl . '/api/users/' . $id);
        
        if ($response->getStatusCode() === 404) {
            return null;
        }
        
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Failed to fetch user from Users microservice');
        }
        
        return $response->toArray();
    }
}
