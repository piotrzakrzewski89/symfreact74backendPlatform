<?php

declare(strict_types=1);

namespace App\Application\DTO;

class BookDTO
{
    public function __construct(
        public int $id,
        public string $title,
        public string $author,
        public string $description,
        public string $createdAt,
    ) {}
}
