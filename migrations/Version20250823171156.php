<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250823171156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_by_id INTEGER DEFAULT NULL, status VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , turn_duration_sec INTEGER NOT NULL, visibility VARCHAR(16) NOT NULL, fen CLOB NOT NULL, ply INTEGER NOT NULL, turn_team VARCHAR(1) NOT NULL, turn_deadline DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , result CLOB DEFAULT NULL --(DC2Type:json)
        , PRIMARY KEY(id), CONSTRAINT FK_232B318CB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_232B318CB03A8386 ON game (created_by_id)');
        $this->addSql('CREATE TABLE invite (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , game_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , code VARCHAR(16) NOT NULL, expires_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_C7E210D7E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C7E210D777153098 ON invite (code)');
        $this->addSql('CREATE INDEX IDX_C7E210D7E48FD905 ON invite (game_id)');
        $this->addSql('CREATE INDEX idx_invite_code ON invite (code)');
        $this->addSql('CREATE TABLE move (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , game_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , team_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , by_user_id INTEGER DEFAULT NULL, ply INTEGER NOT NULL, uci VARCHAR(255) DEFAULT NULL, san VARCHAR(255) DEFAULT NULL, fen_after CLOB NOT NULL, type VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_EF3E3778E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_EF3E3778296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_EF3E3778DC9C2434 FOREIGN KEY (by_user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_EF3E3778E48FD905 ON move (game_id)');
        $this->addSql('CREATE INDEX IDX_EF3E3778296CD8AE ON move (team_id)');
        $this->addSql('CREATE INDEX IDX_EF3E3778DC9C2434 ON move (by_user_id)');
        $this->addSql('CREATE INDEX IDX_EF3E3778E48FD9054B215C71 ON move (game_id, ply)');
        $this->addSql('CREATE TABLE team (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , game_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , name VARCHAR(1) NOT NULL, current_index INTEGER NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_C4E0A61FE48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_C4E0A61FE48FD905 ON team (game_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_game_name ON team (game_id, name)');
        $this->addSql('CREATE TABLE team_member (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , team_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , user_id INTEGER NOT NULL, position INTEGER NOT NULL, active BOOLEAN NOT NULL, joined_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_6FFBDA1296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6FFBDA1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_6FFBDA1296CD8AE ON team_member (team_id)');
        $this->addSql('CREATE INDEX IDX_6FFBDA1A76ED395 ON team_member (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_team_user ON team_member (team_id, user_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, display_name, created_at FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, display_name VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('INSERT INTO user (id, email, roles, password, display_name, created_at) SELECT id, email, roles, password, display_name, created_at FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE invite');
        $this->addSql('DROP TABLE move');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE team_member');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, display_name, created_at FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, display_name VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO user (id, email, roles, password, display_name, created_at) SELECT id, email, roles, password, display_name, created_at FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
    }
}
