<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250713152422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;

        for ($i = 1; $i <= 100; $i++) {
            $title = 'Book Title ' . $i;
            $author = 'Author ' . $i;
            $description = 'Description for Book ' . $i;

            $conn->insert('book', [
                'title' => $title,
                'author' => $author,
                'description' => $description,
            ]);
        }
    }

    public function down(Schema $schema): void {}
}
