<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Service\CompanyMicroserviceService;
use App\Application\Service\UsersMicroserviceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/platform', name: 'api_platform_')]
#[IsGranted('ROLE_USER')]
final class UserProfileController extends AbstractController
{
    public function __construct(
        private UsersMicroserviceService $usersService,
        private CompanyMicroserviceService $companyService
    ) {
    }

    #[Route('/profile', methods: ['GET'])]
    public function getProfile(#[CurrentUser] $user): JsonResponse
    {
        try {
            // Get user UUID from JWT token
            $userId = $user->getId();
            
            // Fetch user data from Users microservice
            $userData = $this->usersService->getUserById($userId->toString());
            
            if (!$userData) {
                return $this->json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
            }
            
            // Enrich user data with created_by user info (UUID 00000000-0000-4000-8000-000000000001 = admin)
            $createdByUuid = '00000000-0000-4000-8000-000000000001'; // Default to admin
            $createdByUser = $this->usersService->getUserById($createdByUuid);
            if ($createdByUser) {
                $userData['createdByUser'] = [
                    'uuid' => $createdByUser['uuid'],
                    'firstName' => $createdByUser['firstName'] ?? '',
                    'lastName' => $createdByUser['lastName'] ?? '',
                    'email' => $createdByUser['email'] ?? ''
                ];
            }
            
            // Add company name from Company microservice
            if (isset($userData['companyUuid'])) {
                $companyData = $this->companyService->getCompanyById($userData['companyUuid']);
                if ($companyData) {
                    $userData['companyName'] = $companyData['longName'] ?? $companyData['shortName'] ?? 'Nieznana firma';
                } else {
                    $userData['companyName'] = 'Nieznana firma';
                }
            }
            
            return $this->json([
                'success' => true,
                'user' => $userData
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to fetch user profile: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/profile/me', methods: ['GET'])]
    public function getCurrentUser(#[CurrentUser] $user): JsonResponse
    {
        try {
            // Get user UUID from JWT token
            $userId = $user->getId();
            
            // Fetch user data from Users microservice
            $userData = $this->usersService->getUserById($userId->toString());
            
            return $this->json([
                'success' => true,
                'user' => $userData
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to fetch current user: ' . $e->getMessage()
            ], 500);
        }
    }
}
