<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Framework\Repository\UserPresenceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: UserPresenceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class UserPresence
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid')]
    private Ulid $id;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'presence')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status; // online, offline, away, busy

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastSeen;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $currentChatRoom = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = new Ulid();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->lastSeen = new \DateTimeImmutable();
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getLastSeen(): \DateTimeImmutable
    {
        return $this->lastSeen;
    }

    public function setLastSeen(\DateTimeImmutable $lastSeen): self
    {
        $this->lastSeen = $lastSeen;
        return $this;
    }

    public function getCurrentChatRoom(): ?string
    {
        return $this->currentChatRoom;
    }

    public function setCurrentChatRoom(?string $currentChatRoom): self
    {
        $this->currentChatRoom = $currentChatRoom;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isOnline(): bool
    {
        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $this->lastSeen->getTimestamp();
        return $this->status === 'online' && $diff < 300; // 5 minutes
    }
}
