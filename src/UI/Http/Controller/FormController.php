<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Message\SendMailMessage;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class FormController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $bus,
        private LoggerInterface $logger
    ) {}

    #[Route('/api/form-mail', name: 'api_form_mail', methods: ['POST'])]
    public function getForm(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $this->logger->info('Dispatching SendMailMessage', $data); // 👈 dodaj to

        $massge = new SendMailMessage(
            $data['recipient'] ?? '',
            $data['subject'] ?? '',
            $data['body'] ?? ''
        );

        $this->bus->dispatch($massge);

        return new JsonResponse(['status' => 'Mail queued']);
    }
}
