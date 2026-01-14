<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260113185507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Zmiana chat_message: usunięcie relacji do User, dodanie sender_id i recipient_id jako UUID
        $this->addSql('ALTER TABLE chat_message DROP CONSTRAINT IF EXISTS fk_fab3fc16f624b39d');
        $this->addSql('ALTER TABLE chat_message DROP CONSTRAINT IF EXISTS fk_fab3fc16e92f8f78');
        $this->addSql('DROP INDEX IF EXISTS idx_fab3fc16f624b39d');
        $this->addSql('DROP INDEX IF EXISTS idx_fab3fc16e92f8f78');
        
        $this->addSql('ALTER TABLE chat_message ADD sender_id UUID NOT NULL DEFAULT \'00000000-0000-0000-0000-000000000000\'');
        $this->addSql('ALTER TABLE chat_message ADD recipient_id UUID NOT NULL DEFAULT \'00000000-0000-0000-0000-000000000000\'');
        $this->addSql('COMMENT ON COLUMN chat_message.sender_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN chat_message.recipient_id IS \'(DC2Type:uuid)\'');
        
        $this->addSql('ALTER TABLE chat_message ALTER COLUMN sender_id DROP DEFAULT');
        $this->addSql('ALTER TABLE chat_message ALTER COLUMN recipient_id DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE user_presence DROP CONSTRAINT FK_89FA23A5A76ED395');
        $this->addSql('ALTER TABLE chat_room DROP CONSTRAINT FK_D403CCDAB03A8386');
        $this->addSql('ALTER TABLE chat_room_participants DROP CONSTRAINT FK_223BBAD81819BCFA');
        $this->addSql('ALTER TABLE chat_room_participants DROP CONSTRAINT FK_223BBAD8A76ED395');
        $this->addSql('DROP TABLE chat_room');
        $this->addSql('DROP TABLE chat_room_participants');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('CREATE INDEX idx_fab3fc16f624b39d ON chat_message (sender_id)');
    }
}
