<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250904231606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE user_id_seq CASCADE');
        $this->addSql('ALTER TABLE "user" ALTER id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN "user".id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE game ALTER created_by_id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN game.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE move ALTER by_user_id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN move.by_user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE team_member ALTER user_id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN team_member.user_id IS \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE team_member ALTER user_id TYPE INT');
        $this->addSql('COMMENT ON COLUMN team_member.user_id IS NULL');
        $this->addSql('ALTER TABLE move ALTER by_user_id TYPE INT');
        $this->addSql('COMMENT ON COLUMN move.by_user_id IS NULL');
        $this->addSql('ALTER TABLE game ALTER created_by_id TYPE INT');
        $this->addSql('COMMENT ON COLUMN game.created_by_id IS NULL');
        $this->addSql('ALTER TABLE "user" ALTER id TYPE INT');
        $this->addSql('COMMENT ON COLUMN "user".id IS NULL');
    }
}
