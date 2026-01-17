<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260114150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create books table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE books (
            id UUID NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            price NUMERIC(10, 2) NOT NULL,
            quantity INTEGER NOT NULL,
            cover_image VARCHAR(255) DEFAULT NULL,
            category VARCHAR(100) DEFAULT NULL,
            owner_uuid UUID NOT NULL,
            owner_name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        
        $this->addSql('CREATE INDEX idx_books_owner_uuid ON books (owner_uuid)');
        $this->addSql('CREATE INDEX idx_books_category ON books (category)');
        $this->addSql('CREATE INDEX idx_books_quantity ON books (quantity)');
        $this->addSql('CREATE INDEX idx_books_created_at ON books (created_at)');
        $this->addSql('CREATE INDEX idx_books_price ON books (price)');
        $this->addSql('CREATE INDEX idx_books_title ON books (title)');
        
        // Add full-text search index for title and description (PostgreSQL specific)
        $this->addSql("CREATE INDEX idx_books_search ON books USING gin(to_tsvector('english', title || ' ' || COALESCE(description, '')))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE books');
    }
}
