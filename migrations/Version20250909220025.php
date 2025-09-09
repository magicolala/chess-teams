<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250909220025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add timeout decision fields to game (timeoutDecisionPending, timeoutTimedOutTeam, timeoutDecisionTeam)';
    }

    public function up(Schema $schema): void
    {
        // Postgres-compatible SQL
        $this->addSql("ALTER TABLE game ADD COLUMN timeout_decision_pending BOOLEAN DEFAULT false NOT NULL");
        $this->addSql("ALTER TABLE game ADD COLUMN timeout_timed_out_team VARCHAR(1) DEFAULT NULL");
        $this->addSql("ALTER TABLE game ADD COLUMN timeout_decision_team VARCHAR(1) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game DROP COLUMN timeout_decision_pending');
        $this->addSql('ALTER TABLE game DROP COLUMN timeout_timed_out_team');
        $this->addSql('ALTER TABLE game DROP COLUMN timeout_decision_team');
    }
}
