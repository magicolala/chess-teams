<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250919120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Hand & Brain metadata columns to game table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game ADD hand_brain_current_role VARCHAR(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD hand_brain_piece_hint VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD hand_brain_brain_member_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD hand_brain_hand_member_id UUID DEFAULT NULL');
        $this->addSql("COMMENT ON COLUMN game.hand_brain_brain_member_id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN game.hand_brain_hand_member_id IS '(DC2Type:uuid)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game DROP hand_brain_current_role');
        $this->addSql('ALTER TABLE game DROP hand_brain_piece_hint');
        $this->addSql('ALTER TABLE game DROP hand_brain_brain_member_id');
        $this->addSql('ALTER TABLE game DROP hand_brain_hand_member_id');
    }
}
