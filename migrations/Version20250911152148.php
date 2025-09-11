<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250911152148 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game_role (id UUID NOT NULL, game_id UUID NOT NULL, user_id UUID NOT NULL, team_name VARCHAR(1) NOT NULL, role VARCHAR(16) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BC7CE646E48FD905 ON game_role (game_id)');
        $this->addSql('CREATE INDEX IDX_BC7CE646A76ED395 ON game_role (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_game_user_role ON game_role (game_id, user_id)');
        $this->addSql('COMMENT ON COLUMN game_role.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game_role.game_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game_role.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE game_werewolf_score_log (id UUID NOT NULL, game_id UUID NOT NULL, user_id UUID NOT NULL, reason VARCHAR(32) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_67859BE7E48FD905 ON game_werewolf_score_log (game_id)');
        $this->addSql('CREATE INDEX IDX_67859BE7A76ED395 ON game_werewolf_score_log (user_id)');
        $this->addSql('COMMENT ON COLUMN game_werewolf_score_log.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game_werewolf_score_log.game_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game_werewolf_score_log.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game_werewolf_score_log.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE game_werewolf_vote (id UUID NOT NULL, game_id UUID NOT NULL, voter_id UUID NOT NULL, suspect_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8DC34B94E48FD905 ON game_werewolf_vote (game_id)');
        $this->addSql('CREATE INDEX IDX_8DC34B94EBB4B8AD ON game_werewolf_vote (voter_id)');
        $this->addSql('CREATE INDEX IDX_8DC34B9471812EB2 ON game_werewolf_vote (suspect_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_game_voter_vote ON game_werewolf_vote (game_id, voter_id)');
        $this->addSql('COMMENT ON COLUMN game_werewolf_vote.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game_werewolf_vote.game_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game_werewolf_vote.voter_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game_werewolf_vote.suspect_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game_werewolf_vote.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE user_werewolf_stats (id UUID NOT NULL, user_id UUID NOT NULL, correct_identifications INT DEFAULT 0 NOT NULL, werewolf_successes INT DEFAULT 0 NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5B62C56DA76ED395 ON user_werewolf_stats (user_id)');
        $this->addSql('COMMENT ON COLUMN user_werewolf_stats.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_werewolf_stats.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_werewolf_stats.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE game_role ADD CONSTRAINT FK_BC7CE646E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_role ADD CONSTRAINT FK_BC7CE646A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_werewolf_score_log ADD CONSTRAINT FK_67859BE7E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_werewolf_score_log ADD CONSTRAINT FK_67859BE7A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_werewolf_vote ADD CONSTRAINT FK_8DC34B94E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_werewolf_vote ADD CONSTRAINT FK_8DC34B94EBB4B8AD FOREIGN KEY (voter_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_werewolf_vote ADD CONSTRAINT FK_8DC34B9471812EB2 FOREIGN KEY (suspect_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_werewolf_stats ADD CONSTRAINT FK_5B62C56DA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD mode VARCHAR(16) DEFAULT \'classic\' NOT NULL');
        $this->addSql('ALTER TABLE game ADD two_wolves_enabled BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE game ADD vote_open BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE game ADD vote_started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN game.vote_started_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE game_role DROP CONSTRAINT FK_BC7CE646E48FD905');
        $this->addSql('ALTER TABLE game_role DROP CONSTRAINT FK_BC7CE646A76ED395');
        $this->addSql('ALTER TABLE game_werewolf_score_log DROP CONSTRAINT FK_67859BE7E48FD905');
        $this->addSql('ALTER TABLE game_werewolf_score_log DROP CONSTRAINT FK_67859BE7A76ED395');
        $this->addSql('ALTER TABLE game_werewolf_vote DROP CONSTRAINT FK_8DC34B94E48FD905');
        $this->addSql('ALTER TABLE game_werewolf_vote DROP CONSTRAINT FK_8DC34B94EBB4B8AD');
        $this->addSql('ALTER TABLE game_werewolf_vote DROP CONSTRAINT FK_8DC34B9471812EB2');
        $this->addSql('ALTER TABLE user_werewolf_stats DROP CONSTRAINT FK_5B62C56DA76ED395');
        $this->addSql('DROP TABLE game_role');
        $this->addSql('DROP TABLE game_werewolf_score_log');
        $this->addSql('DROP TABLE game_werewolf_vote');
        $this->addSql('DROP TABLE user_werewolf_stats');
        $this->addSql('ALTER TABLE game DROP mode');
        $this->addSql('ALTER TABLE game DROP two_wolves_enabled');
        $this->addSql('ALTER TABLE game DROP vote_open');
        $this->addSql('ALTER TABLE game DROP vote_started_at');
    }
}
