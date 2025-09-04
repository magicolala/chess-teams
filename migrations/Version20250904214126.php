<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250904214126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, display_name, created_at FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, display_name VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id))');
        $this->addSql('INSERT INTO user (id, email, roles, password, display_name, created_at) SELECT id, email, roles, password, display_name, created_at FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__game AS SELECT id, created_by_id, status, created_at, updated_at, turn_duration_sec, visibility, fen, ply, turn_team, turn_deadline, result, fast_mode_enabled, fast_mode_deadline, consecutive_timeouts, last_timeout_team FROM game');
        $this->addSql('DROP TABLE game');
        $this->addSql('CREATE TABLE game (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_by_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , status VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , turn_duration_sec INTEGER NOT NULL, visibility VARCHAR(16) NOT NULL, fen CLOB NOT NULL, ply INTEGER NOT NULL, turn_team VARCHAR(1) NOT NULL, turn_deadline DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , result VARCHAR(16) DEFAULT NULL, fast_mode_enabled BOOLEAN DEFAULT 0 NOT NULL, fast_mode_deadline DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , consecutive_timeouts INTEGER DEFAULT 0 NOT NULL, last_timeout_team VARCHAR(1) DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_232B318CB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO game (id, created_by_id, status, created_at, updated_at, turn_duration_sec, visibility, fen, ply, turn_team, turn_deadline, result, fast_mode_enabled, fast_mode_deadline, consecutive_timeouts, last_timeout_team) SELECT id, created_by_id, status, created_at, updated_at, turn_duration_sec, visibility, fen, ply, turn_team, turn_deadline, result, fast_mode_enabled, fast_mode_deadline, consecutive_timeouts, last_timeout_team FROM __temp__game');
        $this->addSql('DROP TABLE __temp__game');
        $this->addSql('CREATE INDEX IDX_232B318CB03A8386 ON game (created_by_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__invite AS SELECT id, game_id, code, expires_at FROM invite');
        $this->addSql('DROP TABLE invite');
        $this->addSql('CREATE TABLE invite (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , game_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , code VARCHAR(16) NOT NULL, expires_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_C7E210D7E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO invite (id, game_id, code, expires_at) SELECT id, game_id, code, expires_at FROM __temp__invite');
        $this->addSql('DROP TABLE __temp__invite');
        $this->addSql('CREATE INDEX idx_invite_code ON invite (code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C7E210D7E48FD905 ON invite (game_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C7E210D777153098 ON invite (code)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__move AS SELECT id, game_id, team_id, by_user_id, ply, uci, san, fen_after, type, created_at FROM move');
        $this->addSql('DROP TABLE move');
        $this->addSql('CREATE TABLE move (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , game_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , team_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , by_user_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , ply INTEGER NOT NULL, uci VARCHAR(255) DEFAULT NULL, san VARCHAR(255) DEFAULT NULL, fen_after CLOB NOT NULL, type VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_EF3E3778E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_EF3E3778296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_EF3E3778DC9C2434 FOREIGN KEY (by_user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO move (id, game_id, team_id, by_user_id, ply, uci, san, fen_after, type, created_at) SELECT id, game_id, team_id, by_user_id, ply, uci, san, fen_after, type, created_at FROM __temp__move');
        $this->addSql('DROP TABLE __temp__move');
        $this->addSql('CREATE INDEX IDX_EF3E3778E48FD9054B215C71 ON move (game_id, ply)');
        $this->addSql('CREATE INDEX IDX_EF3E3778DC9C2434 ON move (by_user_id)');
        $this->addSql('CREATE INDEX IDX_EF3E3778296CD8AE ON move (team_id)');
        $this->addSql('CREATE INDEX IDX_EF3E3778E48FD905 ON move (game_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__team AS SELECT id, game_id, name, current_index FROM team');
        $this->addSql('DROP TABLE team');
        $this->addSql('CREATE TABLE team (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , game_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , name VARCHAR(1) NOT NULL, current_index INTEGER NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_C4E0A61FE48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO team (id, game_id, name, current_index) SELECT id, game_id, name, current_index FROM __temp__team');
        $this->addSql('DROP TABLE __temp__team');
        $this->addSql('CREATE UNIQUE INDEX uniq_game_name ON team (game_id, name)');
        $this->addSql('CREATE INDEX IDX_C4E0A61FE48FD905 ON team (game_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__team_member AS SELECT id, game_id, team_id, user_id, position, active, joined_at, ready_to_start FROM team_member');
        $this->addSql('DROP TABLE team_member');
        $this->addSql('CREATE TABLE team_member (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , game_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , team_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , user_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , position INTEGER NOT NULL, active BOOLEAN NOT NULL, joined_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , ready_to_start BOOLEAN NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_6FFBDA1E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6FFBDA1296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6FFBDA1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO team_member (id, game_id, team_id, user_id, position, active, joined_at, ready_to_start) SELECT id, game_id, team_id, user_id, position, active, joined_at, ready_to_start FROM __temp__team_member');
        $this->addSql('DROP TABLE __temp__team_member');
        $this->addSql('CREATE UNIQUE INDEX uniq_game_user ON team_member (game_id, user_id)');
        $this->addSql('CREATE INDEX IDX_6FFBDA1A76ED395 ON team_member (user_id)');
        $this->addSql('CREATE INDEX IDX_6FFBDA1296CD8AE ON team_member (team_id)');
        $this->addSql('CREATE INDEX IDX_6FFBDA1E48FD905 ON team_member (game_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__messenger_messages AS SELECT id, body, headers, queue_name, created_at, available_at, delivered_at FROM messenger_messages');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , available_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , delivered_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('INSERT INTO messenger_messages (id, body, headers, queue_name, created_at, available_at, delivered_at) SELECT id, body, headers, queue_name, created_at, available_at, delivered_at FROM __temp__messenger_messages');
        $this->addSql('DROP TABLE __temp__messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__game AS SELECT id, created_by_id, status, created_at, updated_at, turn_duration_sec, visibility, fen, ply, turn_team, turn_deadline, fast_mode_enabled, fast_mode_deadline, result, consecutive_timeouts, last_timeout_team FROM game');
        $this->addSql('DROP TABLE game');
        $this->addSql('CREATE TABLE game (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_by_id INTEGER DEFAULT NULL, status VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , turn_duration_sec INTEGER NOT NULL, visibility VARCHAR(16) NOT NULL, fen CLOB NOT NULL, ply INTEGER NOT NULL, turn_team VARCHAR(1) NOT NULL, turn_deadline DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , fast_mode_enabled BOOLEAN DEFAULT FALSE NOT NULL, fast_mode_deadline DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , result VARCHAR(16) DEFAULT NULL, consecutive_timeouts INTEGER DEFAULT 0 NOT NULL, last_timeout_team VARCHAR(1) DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_232B318CB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO game (id, created_by_id, status, created_at, updated_at, turn_duration_sec, visibility, fen, ply, turn_team, turn_deadline, fast_mode_enabled, fast_mode_deadline, result, consecutive_timeouts, last_timeout_team) SELECT id, created_by_id, status, created_at, updated_at, turn_duration_sec, visibility, fen, ply, turn_team, turn_deadline, fast_mode_enabled, fast_mode_deadline, result, consecutive_timeouts, last_timeout_team FROM __temp__game');
        $this->addSql('DROP TABLE __temp__game');
        $this->addSql('CREATE INDEX IDX_232B318CB03A8386 ON game (created_by_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__invite AS SELECT id, game_id, code, expires_at FROM invite');
        $this->addSql('DROP TABLE invite');
        $this->addSql('CREATE TABLE invite (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , game_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , code VARCHAR(16) NOT NULL, expires_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_C7E210D7E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO invite (id, game_id, code, expires_at) SELECT id, game_id, code, expires_at FROM __temp__invite');
        $this->addSql('DROP TABLE __temp__invite');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C7E210D777153098 ON invite (code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C7E210D7E48FD905 ON invite (game_id)');
        $this->addSql('CREATE INDEX idx_invite_code ON invite (code)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__messenger_messages AS SELECT id, body, headers, queue_name, created_at, available_at, delivered_at FROM messenger_messages');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , available_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , delivered_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id))');
        $this->addSql('INSERT INTO messenger_messages (id, body, headers, queue_name, created_at, available_at, delivered_at) SELECT id, body, headers, queue_name, created_at, available_at, delivered_at FROM __temp__messenger_messages');
        $this->addSql('DROP TABLE __temp__messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__move AS SELECT id, game_id, team_id, by_user_id, ply, uci, san, fen_after, type, created_at FROM move');
        $this->addSql('DROP TABLE move');
        $this->addSql('CREATE TABLE move (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , game_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , team_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , by_user_id INTEGER DEFAULT NULL, ply INTEGER NOT NULL, uci VARCHAR(255) DEFAULT NULL, san VARCHAR(255) DEFAULT NULL, fen_after CLOB NOT NULL, type VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_EF3E3778E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_EF3E3778296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_EF3E3778DC9C2434 FOREIGN KEY (by_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO move (id, game_id, team_id, by_user_id, ply, uci, san, fen_after, type, created_at) SELECT id, game_id, team_id, by_user_id, ply, uci, san, fen_after, type, created_at FROM __temp__move');
        $this->addSql('DROP TABLE __temp__move');
        $this->addSql('CREATE INDEX IDX_EF3E3778E48FD905 ON move (game_id)');
        $this->addSql('CREATE INDEX IDX_EF3E3778296CD8AE ON move (team_id)');
        $this->addSql('CREATE INDEX IDX_EF3E3778DC9C2434 ON move (by_user_id)');
        $this->addSql('CREATE INDEX IDX_EF3E3778E48FD9054B215C71 ON move (game_id, ply)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__team AS SELECT id, game_id, name, current_index FROM team');
        $this->addSql('DROP TABLE team');
        $this->addSql('CREATE TABLE team (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , game_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , name VARCHAR(1) NOT NULL, current_index INTEGER NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_C4E0A61FE48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO team (id, game_id, name, current_index) SELECT id, game_id, name, current_index FROM __temp__team');
        $this->addSql('DROP TABLE __temp__team');
        $this->addSql('CREATE INDEX IDX_C4E0A61FE48FD905 ON team (game_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_game_name ON team (game_id, name)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__team_member AS SELECT id, game_id, team_id, user_id, position, active, ready_to_start, joined_at FROM team_member');
        $this->addSql('DROP TABLE team_member');
        $this->addSql('CREATE TABLE team_member (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , game_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , team_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , user_id INTEGER NOT NULL, position INTEGER NOT NULL, active BOOLEAN NOT NULL, ready_to_start BOOLEAN DEFAULT FALSE NOT NULL, joined_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_6FFBDA1E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6FFBDA1296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6FFBDA1A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO team_member (id, game_id, team_id, user_id, position, active, ready_to_start, joined_at) SELECT id, game_id, team_id, user_id, position, active, ready_to_start, joined_at FROM __temp__team_member');
        $this->addSql('DROP TABLE __temp__team_member');
        $this->addSql('CREATE INDEX IDX_6FFBDA1E48FD905 ON team_member (game_id)');
        $this->addSql('CREATE INDEX IDX_6FFBDA1296CD8AE ON team_member (team_id)');
        $this->addSql('CREATE INDEX IDX_6FFBDA1A76ED395 ON team_member (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_game_user ON team_member (game_id, user_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, display_name, created_at FROM "user"');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('CREATE TABLE "user" (id INTEGER NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, display_name VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id))');
        $this->addSql('INSERT INTO "user" (id, email, roles, password, display_name, created_at) SELECT id, email, roles, password, display_name, created_at FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
    }
}
