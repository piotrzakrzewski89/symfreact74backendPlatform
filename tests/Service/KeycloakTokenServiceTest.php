<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Application\Service\KeycloakTokenService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class KeycloakTokenServiceTest extends TestCase
{
    public function testGetAdminTokenReturnsValidToken(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'access_token' => 'test_token_123',
            'expires_in' => 300,
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        
        $service = new KeycloakTokenService(
            $httpClient,
            'http://localhost:8180',
            'sandbox',
            'admin@cms.local',
            'Admin123!',
            'sandbox'
        );

        $token = $service->getAdminToken();

        $this->assertEquals('test_token_123', $token);
    }

    public function testGetAdminTokenCachesToken(): void
    {
        $callCount = 0;
        
        $httpClient = new MockHttpClient(function () use (&$callCount) {
            $callCount++;
            return new MockResponse(json_encode([
                'access_token' => 'cached_token',
                'expires_in' => 300,
            ]));
        });

        $service = new KeycloakTokenService(
            $httpClient,
            'http://localhost:8180',
            'sandbox',
            'admin@cms.local',
            'Admin123!',
            'sandbox'
        );

        $token1 = $service->getAdminToken();
        $token2 = $service->getAdminToken();

        $this->assertEquals($token1, $token2);
        $this->assertEquals(1, $callCount, 'Token should be cached and HTTP client called only once');
    }

    public function testGetAdminTokenThrowsExceptionOnFailure(): void
    {
        $mockResponse = new MockResponse('', ['http_code' => 401]);
        $httpClient = new MockHttpClient($mockResponse);

        $service = new KeycloakTokenService(
            $httpClient,
            'http://localhost:8180',
            'sandbox',
            'wrong@user.com',
            'wrong_password',
            'sandbox'
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to obtain admin token from Keycloak');

        $service->getAdminToken();
    }
}
