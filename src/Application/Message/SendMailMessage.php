<?php

declare(strict_types=1);

namespace App\Application\Message;

final  class SendMailMessage
{
    public function __construct(
        public string $recipient,
        public string $subject,
        public string $body,
    ) {}
}
