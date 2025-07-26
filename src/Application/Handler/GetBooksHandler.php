<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\DTO\BookDTO;
use App\Domain\Repository\BookRepository;

class GetBooksHandler
{
    public function __construct(private BookRepository $bookRepository) {}

    public function __invoke(): array
    {
        return array_map(fn($book) => new BookDTO(
            $book->getId(),
            $book->getTitle(),
            $book->getAuthor(),
            $book->getDescription(),
            $book->getCreatedAt()->format('Y-m-d H:i:s')
        ), $this->bookRepository->findAll());
    }
}
