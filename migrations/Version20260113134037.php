<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260113134037 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE chat_message (id UUID NOT NULL, room_id UUID NOT NULL, sender_id UUID NOT NULL, content TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, edited_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, message_type VARCHAR(20) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FAB3FC1654177093 ON chat_message (room_id)');
        $this->addSql('CREATE INDEX IDX_FAB3FC16F624B39D ON chat_message (sender_id)');
        $this->addSql('COMMENT ON COLUMN chat_message.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN chat_message.room_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN chat_message.sender_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN chat_message.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN chat_message.edited_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN chat_message.read_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE chat_room (id UUID NOT NULL, created_by_id UUID DEFAULT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(20) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D403CCDAB03A8386 ON chat_room (created_by_id)');
        $this->addSql('COMMENT ON COLUMN chat_room.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN chat_room.created_by_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN chat_room.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE chat_room_participants (chat_room_id UUID NOT NULL, user_id UUID NOT NULL, PRIMARY KEY(chat_room_id, user_id))');
        $this->addSql('CREATE INDEX IDX_223BBAD81819BCFA ON chat_room_participants (chat_room_id)');
        $this->addSql('CREATE INDEX IDX_223BBAD8A76ED395 ON chat_room_participants (user_id)');
        $this->addSql('COMMENT ON COLUMN chat_room_participants.chat_room_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN chat_room_participants.user_id IS \'(DC2Type:ulid)\'');
        $this->addSql('CREATE TABLE "user" (id UUID NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('COMMENT ON COLUMN "user".id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE user_presence (id UUID NOT NULL, user_id UUID NOT NULL, status VARCHAR(20) NOT NULL, last_seen TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, current_chat_room VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_89FA23A5A76ED395 ON user_presence (user_id)');
        $this->addSql('COMMENT ON COLUMN user_presence.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN user_presence.user_id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN user_presence.last_seen IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_presence.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_presence.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC1654177093 FOREIGN KEY (room_id) REFERENCES chat_room (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC16F624B39D FOREIGN KEY (sender_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE chat_room ADD CONSTRAINT FK_D403CCDAB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE chat_room_participants ADD CONSTRAINT FK_223BBAD81819BCFA FOREIGN KEY (chat_room_id) REFERENCES chat_room (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE chat_room_participants ADD CONSTRAINT FK_223BBAD8A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_presence ADD CONSTRAINT FK_89FA23A5A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE chat_message DROP CONSTRAINT FK_FAB3FC1654177093');
        $this->addSql('ALTER TABLE chat_message DROP CONSTRAINT FK_FAB3FC16F624B39D');
        $this->addSql('ALTER TABLE chat_room DROP CONSTRAINT FK_D403CCDAB03A8386');
        $this->addSql('ALTER TABLE chat_room_participants DROP CONSTRAINT FK_223BBAD81819BCFA');
        $this->addSql('ALTER TABLE chat_room_participants DROP CONSTRAINT FK_223BBAD8A76ED395');
        $this->addSql('ALTER TABLE user_presence DROP CONSTRAINT FK_89FA23A5A76ED395');
        $this->addSql('DROP TABLE chat_message');
        $this->addSql('DROP TABLE chat_room');
        $this->addSql('DROP TABLE chat_room_participants');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE user_presence');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
