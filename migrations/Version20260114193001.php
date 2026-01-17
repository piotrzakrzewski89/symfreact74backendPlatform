<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260114193001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categories (id UUID NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, is_default BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3AF346685E237E06 ON categories (name)');
        $this->addSql('COMMENT ON COLUMN categories.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN categories.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN categories.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE chat_room (id UUID NOT NULL, created_by_id UUID DEFAULT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(20) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D403CCDAB03A8386 ON chat_room (created_by_id)');
        $this->addSql('COMMENT ON COLUMN chat_room.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN chat_room.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN chat_room.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE chat_room_participants (chat_room_id UUID NOT NULL, user_id UUID NOT NULL, PRIMARY KEY(chat_room_id, user_id))');
        $this->addSql('CREATE INDEX IDX_223BBAD81819BCFA ON chat_room_participants (chat_room_id)');
        $this->addSql('CREATE INDEX IDX_223BBAD8A76ED395 ON chat_room_participants (user_id)');
        $this->addSql('COMMENT ON COLUMN chat_room_participants.chat_room_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN chat_room_participants.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE "user" (id UUID NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('COMMENT ON COLUMN "user".id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE chat_room ADD CONSTRAINT FK_D403CCDAB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE chat_room_participants ADD CONSTRAINT FK_223BBAD81819BCFA FOREIGN KEY (chat_room_id) REFERENCES chat_room (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE chat_room_participants ADD CONSTRAINT FK_223BBAD8A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP INDEX idx_book_purchases_buyer_uuid');
        $this->addSql('DROP INDEX idx_book_purchases_completed_at');
        $this->addSql('DROP INDEX idx_book_purchases_purchase_date');
        $this->addSql('DROP INDEX idx_book_purchases_seller_uuid');
        $this->addSql('DROP INDEX idx_book_purchases_status');
        $this->addSql('DROP INDEX idx_book_purchases_total_price');
        $this->addSql('DROP INDEX idx_book_purchases_transaction_id');
        $this->addSql('ALTER TABLE book_purchases ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE book_purchases ALTER book_id TYPE UUID');
        $this->addSql('ALTER TABLE book_purchases ALTER buyer_uuid TYPE UUID');
        $this->addSql('ALTER TABLE book_purchases ALTER purchase_date TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE book_purchases ALTER completed_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN book_purchases.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN book_purchases.book_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN book_purchases.buyer_uuid IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN book_purchases.purchase_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN book_purchases.completed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER INDEX idx_book_purchases_book_id RENAME TO IDX_B8CA27B816A2B381');
        $this->addSql('DROP INDEX idx_books_category');
        $this->addSql('DROP INDEX idx_books_created_at');
        $this->addSql('DROP INDEX idx_books_owner_uuid');
        $this->addSql('DROP INDEX idx_books_price');
        $this->addSql('DROP INDEX idx_books_quantity');
        $this->addSql('DROP INDEX idx_books_title');
        $this->addSql('ALTER TABLE books ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE books ALTER owner_uuid TYPE UUID');
        $this->addSql('ALTER TABLE books ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE books ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN books.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN books.owner_uuid IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN books.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN books.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX idx_fab3fc16f624b39d');
        $this->addSql('ALTER TABLE user_presence ADD CONSTRAINT FK_89FA23A5A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE user_presence DROP CONSTRAINT FK_89FA23A5A76ED395');
        $this->addSql('ALTER TABLE chat_room DROP CONSTRAINT FK_D403CCDAB03A8386');
        $this->addSql('ALTER TABLE chat_room_participants DROP CONSTRAINT FK_223BBAD81819BCFA');
        $this->addSql('ALTER TABLE chat_room_participants DROP CONSTRAINT FK_223BBAD8A76ED395');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE chat_room');
        $this->addSql('DROP TABLE chat_room_participants');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('CREATE INDEX idx_fab3fc16f624b39d ON chat_message (sender_id)');
        $this->addSql('ALTER TABLE book_purchases ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE book_purchases ALTER book_id TYPE UUID');
        $this->addSql('ALTER TABLE book_purchases ALTER buyer_uuid TYPE UUID');
        $this->addSql('ALTER TABLE book_purchases ALTER purchase_date TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE book_purchases ALTER completed_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN book_purchases.id IS NULL');
        $this->addSql('COMMENT ON COLUMN book_purchases.book_id IS NULL');
        $this->addSql('COMMENT ON COLUMN book_purchases.buyer_uuid IS NULL');
        $this->addSql('COMMENT ON COLUMN book_purchases.purchase_date IS NULL');
        $this->addSql('COMMENT ON COLUMN book_purchases.completed_at IS NULL');
        $this->addSql('CREATE INDEX idx_book_purchases_buyer_uuid ON book_purchases (buyer_uuid)');
        $this->addSql('CREATE INDEX idx_book_purchases_completed_at ON book_purchases (completed_at)');
        $this->addSql('CREATE INDEX idx_book_purchases_purchase_date ON book_purchases (purchase_date)');
        $this->addSql('CREATE INDEX idx_book_purchases_seller_uuid ON book_purchases (book_id) WHERE (book_id IS NOT NULL)');
        $this->addSql('CREATE INDEX idx_book_purchases_status ON book_purchases (status)');
        $this->addSql('CREATE INDEX idx_book_purchases_total_price ON book_purchases (total_price)');
        $this->addSql('CREATE INDEX idx_book_purchases_transaction_id ON book_purchases (transaction_id)');
        $this->addSql('ALTER INDEX idx_b8ca27b816a2b381 RENAME TO idx_book_purchases_book_id');
        $this->addSql('ALTER TABLE books ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE books ALTER owner_uuid TYPE UUID');
        $this->addSql('ALTER TABLE books ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE books ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN books.id IS NULL');
        $this->addSql('COMMENT ON COLUMN books.owner_uuid IS NULL');
        $this->addSql('COMMENT ON COLUMN books.created_at IS NULL');
        $this->addSql('COMMENT ON COLUMN books.updated_at IS NULL');
        $this->addSql('CREATE INDEX idx_books_category ON books (category)');
        $this->addSql('CREATE INDEX idx_books_created_at ON books (created_at)');
        $this->addSql('CREATE INDEX idx_books_owner_uuid ON books (owner_uuid)');
        $this->addSql('CREATE INDEX idx_books_price ON books (price)');
        $this->addSql('CREATE INDEX idx_books_quantity ON books (quantity)');
        $this->addSql('CREATE INDEX idx_books_title ON books (title)');
    }
}
