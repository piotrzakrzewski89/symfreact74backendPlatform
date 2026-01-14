<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

class JwtUser implements UserInterface
{
    private string $id;
    private string $username;
    private array $roles;
    private ?string $companyUuid;

    public function __construct(string $id, string $username, array $roles = [], ?string $companyUuid = null)
    {
        $this->id = $id;
        $this->username = $username;
        $this->roles = $roles;
        $this->companyUuid = $companyUuid;
    }

    public function getId(): Uuid
    {
        return Uuid::fromString($this->id);
    }

    public function getCompanyUuid(): ?string
    {
        return $this->companyUuid;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        
        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
    }
}
