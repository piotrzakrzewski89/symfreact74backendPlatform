<?php

declare(strict_types=1);

namespace App\Application\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CompanyMicroserviceService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private KeycloakTokenService $keycloakTokenService
    ) {
    }

    public function getCompanyById(string $companyUuid): ?array
    {
        try {
            $token = $this->keycloakTokenService->getAdminToken();
            
            // Get all active companies
            $response = $this->httpClient->request('GET', 'http://company-www/api/company/active', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
            
            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Failed to fetch companies from Company microservice');
            }
            
            $companies = $response->toArray();
            
            // Find company by UUID
            foreach ($companies as $company) {
                if (isset($company['uuid']) && $company['uuid'] === $companyUuid) {
                    return $company;
                }
            }
            
            return null;
        } catch (\Exception $e) {
            // Log error but don't fail - company data is optional
            error_log('Failed to fetch company: ' . $e->getMessage());
            return null;
        }
    }
}
