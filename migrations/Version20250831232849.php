<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250831232849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__game AS SELECT id, created_by_id, status, created_at, updated_at, turn_duration_sec, visibility, fen, ply, turn_team, turn_deadline, result, fast_mode_enabled, fast_mode_deadline FROM game');
        $this->addSql('DROP TABLE game');
        $this->addSql('CREATE TABLE game (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_by_id INTEGER DEFAULT NULL, status VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , turn_duration_sec INTEGER NOT NULL, visibility VARCHAR(16) NOT NULL, fen CLOB NOT NULL, ply INTEGER NOT NULL, turn_team VARCHAR(1) NOT NULL, turn_deadline DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , result VARCHAR(16) DEFAULT NULL, fast_mode_enabled BOOLEAN DEFAULT 0 NOT NULL, fast_mode_deadline DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , consecutive_timeouts INTEGER DEFAULT 0 NOT NULL, last_timeout_team VARCHAR(1) DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_232B318CB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO game (id, created_by_id, status, created_at, updated_at, turn_duration_sec, visibility, fen, ply, turn_team, turn_deadline, result, fast_mode_enabled, fast_mode_deadline) SELECT id, created_by_id, status, created_at, updated_at, turn_duration_sec, visibility, fen, ply, turn_team, turn_deadline, result, fast_mode_enabled, fast_mode_deadline FROM __temp__game');
        $this->addSql('DROP TABLE __temp__game');
        $this->addSql('CREATE INDEX IDX_232B318CB03A8386 ON game (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__game AS SELECT id, created_by_id, status, created_at, updated_at, turn_duration_sec, visibility, fen, ply, turn_team, turn_deadline, fast_mode_enabled, fast_mode_deadline, result FROM game');
        $this->addSql('DROP TABLE game');
        $this->addSql('CREATE TABLE game (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_by_id INTEGER DEFAULT NULL, status VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , turn_duration_sec INTEGER NOT NULL, visibility VARCHAR(16) NOT NULL, fen CLOB NOT NULL, ply INTEGER NOT NULL, turn_team VARCHAR(1) NOT NULL, turn_deadline DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , fast_mode_enabled BOOLEAN DEFAULT 0 NOT NULL, fast_mode_deadline DATETIME DEFAULT NULL, result VARCHAR(16) DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_232B318CB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO game (id, created_by_id, status, created_at, updated_at, turn_duration_sec, visibility, fen, ply, turn_team, turn_deadline, fast_mode_enabled, fast_mode_deadline, result) SELECT id, created_by_id, status, created_at, updated_at, turn_duration_sec, visibility, fen, ply, turn_team, turn_deadline, fast_mode_enabled, fast_mode_deadline, result FROM __temp__game');
        $this->addSql('DROP TABLE __temp__game');
        $this->addSql('CREATE INDEX IDX_232B318CB03A8386 ON game (created_by_id)');
    }
}
