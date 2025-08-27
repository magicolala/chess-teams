<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250827125736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__invite AS SELECT id, game_id, code, expires_at FROM invite');
        $this->addSql('DROP TABLE invite');
        $this->addSql('CREATE TABLE invite (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , game_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , code VARCHAR(16) NOT NULL, expires_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_C7E210D7E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO invite (id, game_id, code, expires_at) SELECT id, game_id, code, expires_at FROM __temp__invite');
        $this->addSql('DROP TABLE __temp__invite');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C7E210D777153098 ON invite (code)');
        $this->addSql('CREATE INDEX idx_invite_code ON invite (code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C7E210D7E48FD905 ON invite (game_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__invite AS SELECT id, game_id, code, expires_at FROM invite');
        $this->addSql('DROP TABLE invite');
        $this->addSql('CREATE TABLE invite (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , game_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , code VARCHAR(16) NOT NULL, expires_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , PRIMARY KEY(id), CONSTRAINT FK_C7E210D7E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO invite (id, game_id, code, expires_at) SELECT id, game_id, code, expires_at FROM __temp__invite');
        $this->addSql('DROP TABLE __temp__invite');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C7E210D777153098 ON invite (code)');
        $this->addSql('CREATE INDEX idx_invite_code ON invite (code)');
        $this->addSql('CREATE INDEX IDX_C7E210D7E48FD905 ON invite (game_id)');
    }
}
