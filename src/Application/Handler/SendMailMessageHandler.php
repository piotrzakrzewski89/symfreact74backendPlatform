<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Message\SendMailMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler] // dla autowire / discovery
final class SendMailMessageHandler
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function __invoke(SendMailMessage $message): void
    {
        $this->logger->info('Handling SendMailMessage', [
            'to' => $message->recipient,
            'subject' => $message->subject,
        ]);

        // obsługa wysyłki maila
        // np. logika: mailer->send(...) itd.
        dump('Wysylam maila do: ' . $message->recipient);
    }
}
