<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260114150001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create book_purchases table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE book_purchases (
            id UUID NOT NULL,
            book_id UUID NOT NULL,
            buyer_uuid UUID NOT NULL,
            buyer_name VARCHAR(255) NOT NULL,
            buyer_email VARCHAR(255) NOT NULL,
            quantity INTEGER NOT NULL,
            purchase_price NUMERIC(10, 2) NOT NULL,
            total_price NUMERIC(10, 2) NOT NULL,
            status VARCHAR(20) NOT NULL,
            purchase_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            payment_method VARCHAR(255) DEFAULT NULL,
            transaction_id VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY(id),
            CONSTRAINT fk_book_purchases_book FOREIGN KEY (book_id) REFERENCES books (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        )');
        
        $this->addSql('CREATE INDEX idx_book_purchases_book_id ON book_purchases (book_id)');
        $this->addSql('CREATE INDEX idx_book_purchases_buyer_uuid ON book_purchases (buyer_uuid)');
        $this->addSql('CREATE INDEX idx_book_purchases_status ON book_purchases (status)');
        $this->addSql('CREATE INDEX idx_book_purchases_purchase_date ON book_purchases (purchase_date)');
        $this->addSql('CREATE INDEX idx_book_purchases_completed_at ON book_purchases (completed_at)');
        $this->addSql('CREATE INDEX idx_book_purchases_total_price ON book_purchases (total_price)');
        $this->addSql('CREATE INDEX idx_book_purchases_transaction_id ON book_purchases (transaction_id)');
        
        // Add index for seller (book owner) queries
        $this->addSql('CREATE INDEX idx_book_purchases_seller_uuid ON book_purchases (book_id) WHERE book_id IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE book_purchases');
    }
}
