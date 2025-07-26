<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Handler\GetBooksHandler;
use App\Domain\Entity\Book;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

class BookController extends AbstractController
{
    public function __construct(private GetBooksHandler $getBooksHandler) {}

    #[OA\Get(
        path: '/books', // zakładając, że masz prefix /api globalnie w routingu
        summary: 'Get list of books',
        tags: ['Book'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of books',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: Book::class))
                )
            )
        ]
    )]
    #[Route('/api/books', name: 'api_books_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $books = ($this->getBooksHandler)();
        return $this->json(array_values($books));
    }
}
