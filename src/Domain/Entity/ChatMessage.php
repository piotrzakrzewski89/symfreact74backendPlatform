<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Infrastructure\Framework\Repository\ChatMessageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: ChatMessageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ChatMessage
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid')]
    private Ulid $id;

    #[ORM\Column(type: 'uuid', nullable: false)]
    private \Symfony\Component\Uid\Uuid $senderId;

    #[ORM\Column(type: 'uuid', nullable: false)]
    private \Symfony\Component\Uid\Uuid $recipientId;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $editedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $messageType = 'text'; // text, image, file, system

    public function __construct()
    {
        $this->id = new Ulid();
    }

    public function getId(): Ulid
    {
        return $this->id;
    }


    public function getSenderId(): \Symfony\Component\Uid\Uuid
    {
        return $this->senderId;
    }

    public function setSenderId(\Symfony\Component\Uid\Uuid $senderId): self
    {
        $this->senderId = $senderId;
        return $this;
    }

    public function getRecipientId(): \Symfony\Component\Uid\Uuid
    {
        return $this->recipientId;
    }

    public function setRecipientId(\Symfony\Component\Uid\Uuid $recipientId): self
    {
        $this->recipientId = $recipientId;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAt(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getEditedAt(): ?\DateTimeImmutable
    {
        return $this->editedAt;
    }

    public function setEditedAt(?\DateTimeImmutable $editedAt): self
    {
        $this->editedAt = $editedAt;
        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeImmutable $readAt): self
    {
        $this->readAt = $readAt;
        return $this;
    }

    public function getMessageType(): ?string
    {
        return $this->messageType;
    }

    public function setMessageType(?string $messageType): self
    {
        $this->messageType = $messageType;
        return $this;
    }
}
