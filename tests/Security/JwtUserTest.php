<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\JwtUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class JwtUserTest extends TestCase
{
    public function testGetIdReturnsUuid(): void
    {
        $uuidString = '123e4567-e89b-12d3-a456-426614174000';
        $user = new JwtUser($uuidString, 'test@test.com', ['ROLE_USER']);

        $id = $user->getId();

        $this->assertInstanceOf(Uuid::class, $id);
        $this->assertEquals($uuidString, $id->toString());
    }

    public function testGetUserIdentifierReturnsUsername(): void
    {
        $user = new JwtUser('123e4567-e89b-12d3-a456-426614174000', 'test@test.com', ['ROLE_USER']);

        $this->assertEquals('test@test.com', $user->getUserIdentifier());
    }

    public function testGetRolesIncludesDefaultRole(): void
    {
        $user = new JwtUser('123e4567-e89b-12d3-a456-426614174000', 'test@test.com', ['ROLE_ADMIN']);

        $roles = $user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    public function testGetRolesReturnsUniqueRoles(): void
    {
        $user = new JwtUser('123e4567-e89b-12d3-a456-426614174000', 'test@test.com', ['ROLE_USER', 'ROLE_USER']);

        $roles = $user->getRoles();

        $this->assertCount(1, $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testEraseCredentialsDoesNothing(): void
    {
        $user = new JwtUser('123e4567-e89b-12d3-a456-426614174000', 'test@test.com', ['ROLE_USER']);

        $user->eraseCredentials();

        $this->assertEquals('test@test.com', $user->getUserIdentifier());
    }
}
