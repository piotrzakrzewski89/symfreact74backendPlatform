<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\User;
use App\Infrastructure\Framework\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class UserSynchronizationService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function synchronizeUserFromKeycloak(array $keycloakData): User
    {
        $email = $keycloakData['email'] ?? null;
        if (!$email) {
            throw new \InvalidArgumentException('Email is required from Keycloak data');
        }

        $user = $this->userRepository->findByEmail($email);
        
        if (!$user) {
            // Tworzymy nowego użytkownika
            $user = new User();
            $user->setEmail($email);
            $user->setFirstName($keycloakData['given_name'] ?? '');
            $user->setLastName($keycloakData['family_name'] ?? '');
            $user->setRoles($keycloakData['resource_access']['sandbox']['roles'] ?? ['ROLE_USER']);
            
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } else {
            // Aktualizujemy dane istniejącego użytkownika
            $user->setFirstName($keycloakData['given_name'] ?? $user->getFirstName());
            $user->setLastName($keycloakData['family_name'] ?? $user->getLastName());
            $user->setRoles($keycloakData['resource_access']['sandbox']['roles'] ?? $user->getRoles());
            
            $this->entityManager->flush();
        }
        
        return $user;
    }

    public function synchronizeUserFromToken(string $token, array $claims): User
    {
        // Wyciągamy dane z tokenu JWT
        $userData = [
            'email' => $claims['preferred_username'] ?? $claims['email'] ?? null,
            'given_name' => $claims['given_name'] ?? '',
            'family_name' => $claims['family_name'] ?? '',
            'resource_access' => $claims['resource_access'] ?? []
        ];
        
        return $this->synchronizeUserFromKeycloak($userData);
    }

    public function ensureUserExists(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }
}
