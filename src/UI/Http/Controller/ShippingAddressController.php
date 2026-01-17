<?php

namespace App\UI\Http\Controller;

use App\Application\Service\ShippingAddressService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/shipping-addresses')]
#[IsGranted('ROLE_USER')]
class ShippingAddressController extends AbstractController
{
    public function __construct(
        private readonly ShippingAddressService $addressService
    ) {
    }

    /**
     * Extract user UUID from JWT token
     */
    private function getUserUuidFromToken(Request $request): ?Uuid
    {
        // Get Authorization header
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        // Extract token
        $token = substr($authHeader, 7);
        
        // For simplicity, we'll decode the JWT token to get the UUID
        // In production, you should use a proper JWT library
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        // Decode payload (base64url)
        $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
        if (!$payload) {
            return null;
        }

        $data = json_decode($payload, true);
        if (!$data || !isset($data['user_uuid'])) {
            return null;
        }

        try {
            return Uuid::fromString($data['user_uuid']);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all addresses for current user
     */
    #[Route('', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        try {
            // Get user UUID from JWT token
            $userUuid = $this->getUserUuidFromToken($request);
            if (!$userUuid) {
                return $this->json(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
            }

            $addresses = $this->addressService->getUserAddresses($userUuid);
            
            return $this->json([
                'data' => $this->addressService->addressesToArray($addresses),
                'meta' => [
                    'total' => count($addresses)
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to fetch addresses: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get default address for current user
     */
    #[Route('/default', methods: ['GET'])]
    public function getDefault(Request $request): JsonResponse
    {
        try {
            $userUuid = $this->getUserUuidFromToken($request);
            if (!$userUuid) {
                return $this->json(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
            }

            $address = $this->addressService->getDefaultAddress($userUuid);
            
            if (!$address) {
                return $this->json(['error' => 'No default address found'], Response::HTTP_NOT_FOUND);
            }

            return $this->json([
                'data' => $this->addressService->addressToArray($address)
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to fetch default address: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create new shipping address
     */
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if ($data === null) {
                return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
            }

            // Add user UUID from JWT
            $userUuid = $this->getUserUuidFromToken($request);
            if (!$userUuid) {
                return $this->json(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
            }
            
            $data['userUuid'] = $userUuid->toRfc4122();

            // Validate data
            $errors = $this->addressService->validateAddressData($data);
            if (!empty($errors)) {
                return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $address = $this->addressService->createAddress($data);
            
            return $this->json([
                'data' => $this->addressService->addressToArray($address),
                'message' => 'Address created successfully'
            ], Response::HTTP_CREATED);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to create address: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update existing shipping address
     */
    #[Route('/{uuid}', methods: ['PUT'])]
    public function update(string $uuid, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if ($data === null) {
                return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
            }

            $addressUuid = Uuid::fromString($uuid);
            $errors = $this->addressService->validateAddressData($data, $addressUuid);
            
            if (!empty($errors)) {
                return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $address = $this->addressService->updateAddress($addressUuid, $data);
            
            return $this->json([
                'data' => $this->addressService->addressToArray($address),
                'message' => 'Address updated successfully'
            ]);
            
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Invalid UUID format'], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'Address not found') {
                return $this->json(['error' => 'Address not found'], Response::HTTP_NOT_FOUND);
            }
            return $this->json(['error' => 'Failed to update address'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to update address'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete shipping address
     */
    #[Route('/{uuid}', methods: ['DELETE'])]
    public function delete(string $uuid): JsonResponse
    {
        try {
            $addressUuid = Uuid::fromString($uuid);
            $this->addressService->deleteAddress($addressUuid);
            
            return $this->json([
                'message' => 'Address deleted successfully'
            ]);
            
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Invalid UUID format'], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'Address not found') {
                return $this->json(['error' => 'Address not found'], Response::HTTP_NOT_FOUND);
            }
            return $this->json(['error' => 'Failed to delete address'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to delete address'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Set address as default
     */
    #[Route('/{uuid}/set-default', methods: ['POST'])]
    public function setDefault(string $uuid): JsonResponse
    {
        try {
            $addressUuid = Uuid::fromString($uuid);
            
            // Update address with isDefault flag
            $address = $this->addressService->updateAddress($addressUuid, ['isDefault' => true]);
            
            return $this->json([
                'data' => $this->addressService->addressToArray($address),
                'message' => 'Address set as default'
            ]);
            
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => 'Invalid UUID format'], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'Address not found') {
                return $this->json(['error' => 'Address not found'], Response::HTTP_NOT_FOUND);
            }
            return $this->json(['error' => 'Failed to set default address'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to set default address'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
