<?php

declare(strict_types=1);

namespace App\Application\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class KeycloakTokenService
{
    private ?string $cachedToken = null;
    private ?int $tokenExpiresAt = null;

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $keycloakUrl,
        private string $keycloakRealm,
        private string $adminUsername,
        private string $adminPassword,
        private string $clientId
    ) {
    }

    public function getAdminToken(): string
    {
        // Return cached token if still valid
        if ($this->cachedToken && $this->tokenExpiresAt && time() < $this->tokenExpiresAt - 30) {
            return $this->cachedToken;
        }

        // Request new token
        $response = $this->httpClient->request('POST', 
            sprintf('%s/realms/%s/protocol/openid-connect/token', $this->keycloakUrl, $this->keycloakRealm),
            [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'grant_type' => 'password',
                    'client_id' => $this->clientId,
                    'client_secret' => '',
                    'username' => $this->adminUsername,
                    'password' => $this->adminPassword,
                    'scope' => 'openid profile email',
                ],
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Failed to obtain admin token from Keycloak');
        }

        $data = $response->toArray();
        $this->cachedToken = $data['access_token'];
        $this->tokenExpiresAt = time() + ($data['expires_in'] ?? 300);

        return $this->cachedToken;
    }
}
