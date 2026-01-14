<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Manual migration to simplify chat architecture
 */
final class Version20260113151458 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Simplify chat architecture - remove ChatRoom, add recipient_id to ChatMessage, remove password column';
    }

    public function up(Schema $schema): void
    {
        // Drop chat_room_participants table
        if ($schema->hasTable('chat_room_participants')) {
            $schema->dropTable('chat_room_participants');
        }

        // Drop chat_room table
        if ($schema->hasTable('chat_room')) {
            $schema->dropTable('chat_room');
        }

        // Modify chat_message table - replace room_id with recipient_id
        $chatMessageTable = $schema->getTable('chat_message');
        
        // Drop old room_id column if it exists
        if ($chatMessageTable->hasColumn('room_id')) {
            $chatMessageTable->dropColumn('room_id');
        }

        // Add recipient_id column if it doesn't exist
        if (!$chatMessageTable->hasColumn('recipient_id')) {
            $chatMessageTable->addColumn('recipient_id', 'uuid', [
                'notnull' => true,
                'comment' => '(DC2Type:uuid)'
            ]);
        }

        // Drop password column from user table
        $userTable = $schema->getTable('user');
        if ($userTable->hasColumn('password')) {
            $userTable->dropColumn('password');
        }
    }

    public function down(Schema $schema): void
    {
        // Add back password column
        $userTable = $schema->getTable('user');
        if (!$userTable->hasColumn('password')) {
            $userTable->addColumn('password', 'string', [
                'length' => 255,
                'notnull' => true
            ]);
        }

        // Add back room_id column and drop recipient_id
        $chatMessageTable = $schema->getTable('chat_message');
        if ($chatMessageTable->hasColumn('recipient_id')) {
            $chatMessageTable->addColumn('room_id', 'uuid', [
                'notnull' => true,
                'comment' => '(DC2Type:uuid)'
            ]);
            $chatMessageTable->dropColumn('recipient_id');
        }

        // Recreate chat_room table
        if (!$schema->hasTable('chat_room')) {
            $table = $schema->createTable('chat_room');
            $table->addColumn('id', 'uuid', ['notnull' => true, 'comment' => '(DC2Type:ulid)']);
            $table->addColumn('created_by_id', 'uuid', ['notnull' => false, 'comment' => '(DC2Type:uuid)']);
            $table->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
            $table->addColumn('type', 'string', ['length' => 20, 'notnull' => true]);
            $table->addColumn('description', 'text', ['notnull' => false]);
            $table->addColumn('created_at', 'datetime_immutable', ['notnull' => true, 'comment' => '(DC2Type:datetime_immutable)']);
            $table->addColumn('is_active', 'boolean', ['notnull' => true]);
            $table->setPrimaryKey(['id']);
        }

        // Recreate chat_room_participants table
        if (!$schema->hasTable('chat_room_participants')) {
            $table = $schema->createTable('chat_room_participants');
            $table->addColumn('chat_room_id', 'uuid', ['notnull' => true, 'comment' => '(DC2Type:ulid)']);
            $table->addColumn('user_id', 'uuid', ['notnull' => true, 'comment' => '(DC2Type:ulid)']);
            $table->setPrimaryKey(['chat_room_id', 'user_id']);
        }
    }
}
