<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250827191441 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE team_member ADD COLUMN ready_to_start BOOLEAN NOT NULL DEFAULT FALSE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__team_member AS SELECT id, game_id, team_id, user_id, position, active, joined_at FROM team_member');
        $this->addSql('DROP TABLE team_member');
        $this->addSql('CREATE TABLE team_member (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , game_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , team_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , user_id INTEGER NOT NULL, position INTEGER NOT NULL, active BOOLEAN NOT NULL, joined_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_6FFBDA1E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6FFBDA1296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6FFBDA1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO team_member (id, game_id, team_id, user_id, position, active, joined_at) SELECT id, game_id, team_id, user_id, position, active, joined_at FROM __temp__team_member');
        $this->addSql('DROP TABLE __temp__team_member');
        $this->addSql('CREATE INDEX IDX_6FFBDA1E48FD905 ON team_member (game_id)');
        $this->addSql('CREATE INDEX IDX_6FFBDA1296CD8AE ON team_member (team_id)');
        $this->addSql('CREATE INDEX IDX_6FFBDA1A76ED395 ON team_member (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_game_user ON team_member (game_id, user_id)');
    }
}
