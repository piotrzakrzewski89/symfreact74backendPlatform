<?php

declare(strict_types=1);

namespace App\Application\DTO\Chat;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SendMessageRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Recipient user ID is required')]
        #[Assert\Uuid(message: 'Invalid user ID format')]
        public string $recipientId,

        #[Assert\NotBlank(message: 'Message content cannot be empty')]
        #[Assert\Length(min: 1, max: 2000, minMessage: 'Message too short', maxMessage: 'Message too long')]
        public string $content,

        #[Assert\Choice(choices: ['text', 'file', 'image'], message: 'Invalid message type')]
        public string $type = 'text'
    ) {
    }
}
