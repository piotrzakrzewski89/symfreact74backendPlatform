<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\DTO\Chat\SendMessageRequest;
use App\Application\Service\ChatService;
use App\Application\Service\MercureService;
use App\Application\Service\UserPresenceRedisService;
use App\Application\Service\UsersMicroserviceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/platform', name: 'api_platform_')]
#[IsGranted('ROLE_USER')]
final class ChatController extends AbstractController
{
    public function __construct(
        private ChatService $chatService,
        private UserPresenceRedisService $userPresenceService,
        private UsersMicroserviceService $usersService,
        private ValidatorInterface $validator,
        private MercureService $mercureService
    ) {
    }

    #[Route('/conversations', methods: ['GET'])]
    public function getConversations(#[CurrentUser] $user): JsonResponse
    {
        try {
            $conversations = $this->chatService->getConversations($user->getId());
            
            return $this->json([
                'success' => true,
                'data' => $conversations
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/users', methods: ['GET', 'OPTIONS'])]
    public function getUsers(Request $request, #[CurrentUser] $user): JsonResponse
    {
        // CORS headers dla OPTIONS
        if ($request->getMethod() === 'OPTIONS') {
            return new JsonResponse('', 200, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
                'Access-Control-Max-Age' => '3600'
            ]);
        }

        try {
            // Mark current user as online
            $this->userPresenceService->markUserAsOnline($user->getId());
            
            // UsersMicroserviceService uses admin token internally
            $users = $this->usersService->getAllUsers();
            
            // Get online users from Redis
            $onlineUsers = $this->userPresenceService->getOnlineUsers();
            $onlineUsersMap = [];
            foreach ($onlineUsers as $onlineUser) {
                $onlineUsersMap[$onlineUser['id']] = $onlineUser;
            }
            
            // Get current user's company UUID
            $currentUserCompanyUuid = $user->getCompanyUuid();
            
            // Check if current user is admin
            $currentUserRoles = $user->getRoles();
            $isAdmin = in_array('ROLE_ADMIN_CMS', $currentUserRoles);
            
            // Format users with presence information
            $formattedUsers = [];
            foreach ($users as $userArray) {
                // Skip current user - compare emails
                $currentUserId = $user->getUserIdentifier();
                if ($userArray['email'] === $currentUserId) {
                    continue;
                }
                
                // Skip admins (ROLE_ADMIN_CMS) from the list
                $roles = $userArray['roles'] ?? [];
                if (in_array('ROLE_ADMIN_CMS', $roles)) {
                    continue;
                }
                
                // Skip users from different companies (only if current user is NOT admin)
                if (!$isAdmin && $currentUserCompanyUuid && isset($userArray['companyUuid'])) {
                    if ($userArray['companyUuid'] !== $currentUserCompanyUuid) {
                        continue;
                    }
                }
                
                $userId = $userArray['uuid'];
                $onlineUserData = $onlineUsersMap[$userId] ?? null;
                
                $formattedUsers[] = [
                    'id' => $userId,
                    'firstName' => $userArray['firstName'] ?? '',
                    'lastName' => $userArray['lastName'] ?? '',
                    'fullName' => ($userArray['firstName'] ?? '') . ' ' . ($userArray['lastName'] ?? ''),
                    'avatar' => null,
                    'status' => $onlineUserData['status'] ?? 'offline',
                    'lastSeen' => $onlineUserData['lastSeen'] ?? '',
                    'currentChatRoom' => $onlineUserData['currentChatRoom'] ?? null
                ];
            }
            
            return $this->json([
                'success' => true,
                'data' => $formattedUsers
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/conversation/{otherUserId}', methods: ['GET'])]
    public function getConversation(string $otherUserId, #[CurrentUser] $user): JsonResponse
    {
        try {
            $otherUserUuid = new Uuid($otherUserId);
            $conversation = $this->chatService->getConversation($user->getId(), $otherUserUuid);
            
            if (!$conversation) {
                return $this->json([
                    'success' => false,
                    'error' => 'Conversation not found'
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->json([
                'success' => true,
                'data' => $conversation
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid user ID format'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/messages/{userId}', methods: ['GET'])]
    public function getMessages(string $userId, #[CurrentUser] $user): JsonResponse
    {
        try {
            $otherUserUuid = new Uuid($userId);
            $messages = $this->chatService->getMessages($user->getId(), $otherUserUuid);
            
            return $this->json([
                'success' => true,
                'data' => $messages
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid user ID format'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/messages', methods: ['POST'])]
    public function sendMessage(Request $request, #[CurrentUser] $user): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->json([
                    'success' => false,
                    'error' => 'Invalid JSON'
                ], Response::HTTP_BAD_REQUEST);
            }

            $sendRequest = new SendMessageRequest(
                recipientId: $data['recipientId'] ?? '',
                content: $data['content'] ?? '',
                type: $data['type'] ?? 'text'
            );

            $errors = $this->validator->validate($sendRequest);
            if (count($errors) > 0) {
                return $this->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $errors[0]?->getMessage() ?? 'Unknown validation error'
                ], Response::HTTP_BAD_REQUEST);
            }

            $message = $this->chatService->sendMessage($user->getId(), $sendRequest);

            return $this->json([
                'success' => true,
                'data' => $message
            ], Response::HTTP_CREATED);

        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to send message'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/messages/{roomId}/read', methods: ['POST'])]
    public function markMessagesAsRead(string $roomId, #[CurrentUser] $user): JsonResponse
    {
        try {
            $roomUuid = new Uuid($roomId);
            $this->chatService->markMessagesAsRead($user->getId(), $roomUuid);

            return $this->json([
                'success' => true,
                'message' => 'Messages marked as read'
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid room ID format'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/online-users', methods: ['GET'])]
    public function getOnlineUsers(): JsonResponse
    {
        $onlineUsers = $this->userPresenceService->getOnlineUsers();

        return $this->json([
            'success' => true,
            'data' => $onlineUsers
        ]);
    }

    #[Route('/presence/{userId}', methods: ['GET'])]
    public function getUserPresence(string $userId): JsonResponse
    {
        try {
            $userUlid = new Uuid($userId);
            $presence = $this->userPresenceService->getUserPresence($userUlid);

            if (!$presence) {
                return $this->json([
                    'success' => false,
                    'error' => 'User not found'
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->json([
                'success' => true,
                'data' => $presence
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid user ID format'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/presence', methods: ['PUT'])]
    public function updatePresence(Request $request, #[CurrentUser] $user): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return $this->json([
                    'success' => false,
                    'error' => 'Invalid JSON'
                ], Response::HTTP_BAD_REQUEST);
            }

            $status = $data['status'] ?? 'offline';
            $currentChatRoom = $data['currentChatRoom'] ?? null;

            // Walidacja statusu
            if (!in_array($status, ['online', 'offline', 'away', 'busy'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'Invalid status. Must be: online, offline, away, busy'
                ], Response::HTTP_BAD_REQUEST);
            }

            $presence = $this->userPresenceService->updateUserPresence(
                $user->getId(),
                $status,
                $currentChatRoom
            );

            return $this->json([
                'success' => true,
                'data' => $presence
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/presence/online', methods: ['POST'])]
    public function markAsOnline(#[CurrentUser] $user): JsonResponse
    {
        $this->userPresenceService->markUserAsOnline($user->getId());

        return $this->json([
            'success' => true,
            'message' => 'Marked as online'
        ]);
    }

    #[Route('/presence/offline', methods: ['POST'])]
    public function markAsOffline(#[CurrentUser] $user): JsonResponse
    {
        $this->userPresenceService->markUserAsOffline($user->getId());

        return $this->json([
            'success' => true,
            'message' => 'Marked as offline'
        ]);
    }

    #[Route('/presence/away', methods: ['POST'])]
    public function markAsAway(#[CurrentUser] $user): JsonResponse
    {
        $this->userPresenceService->markUserAsAway($user->getId());

        return $this->json([
            'success' => true,
            'message' => 'Marked as away'
        ]);
    }

    #[Route('/presence/busy', methods: ['POST'])]
    public function markAsBusy(#[CurrentUser] $user): JsonResponse
    {
        $this->userPresenceService->markUserAsBusy($user->getId());
        
        return $this->json([
            'success' => true,
            'message' => 'Status updated to busy'
        ]);
    }

    #[Route('/typing', methods: ['POST'])]
    public function typing(Request $request, #[CurrentUser] $user): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $recipientId = $data['recipientId'] ?? null;
            $isTyping = $data['isTyping'] ?? false;

            if (!$recipientId) {
                return $this->json([
                    'success' => false,
                    'error' => 'Recipient ID is required'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Publikuj typing indicator do Mercure
            $this->mercureService->publishTypingIndicator(
                $user->getId(),
                new Uuid($recipientId),
                $isTyping
            );

            return $this->json([
                'success' => true
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
