<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250909175856 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE "user" (id UUID NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, display_name VARCHAR(50) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('COMMENT ON COLUMN "user".id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE game (id UUID NOT NULL, created_by_id UUID DEFAULT NULL, status VARCHAR(10) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, turn_duration_sec INT NOT NULL, visibility VARCHAR(16) NOT NULL, fen TEXT NOT NULL, ply INT NOT NULL, turn_team VARCHAR(1) NOT NULL, turn_deadline TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, fast_mode_enabled BOOLEAN DEFAULT false NOT NULL, fast_mode_deadline TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, result VARCHAR(16) DEFAULT NULL, consecutive_timeouts INT DEFAULT 0 NOT NULL, last_timeout_team VARCHAR(1) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_232B318CB03A8386 ON game (created_by_id)');
        $this->addSql('COMMENT ON COLUMN game.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN game.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN game.turn_deadline IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN game.fast_mode_deadline IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE invite (id UUID NOT NULL, game_id UUID NOT NULL, code VARCHAR(16) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C7E210D777153098 ON invite (code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C7E210D7E48FD905 ON invite (game_id)');
        $this->addSql('CREATE INDEX idx_invite_code ON invite (code)');
        $this->addSql('COMMENT ON COLUMN invite.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN invite.game_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN invite.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE move (id UUID NOT NULL, game_id UUID NOT NULL, team_id UUID DEFAULT NULL, by_user_id UUID DEFAULT NULL, ply INT NOT NULL, uci VARCHAR(255) DEFAULT NULL, san VARCHAR(255) DEFAULT NULL, fen_after TEXT NOT NULL, type VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EF3E3778E48FD905 ON move (game_id)');
        $this->addSql('CREATE INDEX IDX_EF3E3778296CD8AE ON move (team_id)');
        $this->addSql('CREATE INDEX IDX_EF3E3778DC9C2434 ON move (by_user_id)');
        $this->addSql('CREATE INDEX IDX_EF3E3778E48FD9054B215C71 ON move (game_id, ply)');
        $this->addSql('COMMENT ON COLUMN move.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN move.game_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN move.team_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN move.by_user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN move.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE team (id UUID NOT NULL, game_id UUID NOT NULL, name VARCHAR(1) NOT NULL, current_index INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C4E0A61FE48FD905 ON team (game_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_game_name ON team (game_id, name)');
        $this->addSql('COMMENT ON COLUMN team.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN team.game_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE team_member (id UUID NOT NULL, game_id UUID NOT NULL, team_id UUID NOT NULL, user_id UUID NOT NULL, position INT NOT NULL, active BOOLEAN NOT NULL, ready_to_start BOOLEAN NOT NULL, joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6FFBDA1E48FD905 ON team_member (game_id)');
        $this->addSql('CREATE INDEX IDX_6FFBDA1296CD8AE ON team_member (team_id)');
        $this->addSql('CREATE INDEX IDX_6FFBDA1A76ED395 ON team_member (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_game_user ON team_member (game_id, user_id)');
        $this->addSql('COMMENT ON COLUMN team_member.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN team_member.game_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN team_member.team_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN team_member.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN team_member.joined_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
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
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invite ADD CONSTRAINT FK_C7E210D7E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE move ADD CONSTRAINT FK_EF3E3778E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE move ADD CONSTRAINT FK_EF3E3778296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE move ADD CONSTRAINT FK_EF3E3778DC9C2434 FOREIGN KEY (by_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61FE48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_6FFBDA1E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_6FFBDA1296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_6FFBDA1A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318CB03A8386');
        $this->addSql('ALTER TABLE invite DROP CONSTRAINT FK_C7E210D7E48FD905');
        $this->addSql('ALTER TABLE move DROP CONSTRAINT FK_EF3E3778E48FD905');
        $this->addSql('ALTER TABLE move DROP CONSTRAINT FK_EF3E3778296CD8AE');
        $this->addSql('ALTER TABLE move DROP CONSTRAINT FK_EF3E3778DC9C2434');
        $this->addSql('ALTER TABLE team DROP CONSTRAINT FK_C4E0A61FE48FD905');
        $this->addSql('ALTER TABLE team_member DROP CONSTRAINT FK_6FFBDA1E48FD905');
        $this->addSql('ALTER TABLE team_member DROP CONSTRAINT FK_6FFBDA1296CD8AE');
        $this->addSql('ALTER TABLE team_member DROP CONSTRAINT FK_6FFBDA1A76ED395');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE invite');
        $this->addSql('DROP TABLE move');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE team_member');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
