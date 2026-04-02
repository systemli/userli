<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402203849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add per-domain invitation settings and remove vouchers from non-primary domains';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domains ADD invitation_enabled TINYINT DEFAULT 0 NOT NULL, ADD invitation_limit INT DEFAULT 0 NOT NULL');
    }

    public function postUp(Schema $schema): void
    {
        // Enable invitations with limit 3 for the primary domain (lowest ID)
        $this->connection->executeStatement(
            'UPDATE domains SET invitation_enabled = 1, invitation_limit = 3 WHERE id = (SELECT min_id FROM (SELECT MIN(id) AS min_id FROM domains) AS t)'
        );

        // Delete vouchers from non-primary domains (bug fix: these should never have been created)
        $this->connection->executeStatement(
            'DELETE FROM vouchers WHERE domain_id != (SELECT min_id FROM (SELECT MIN(id) AS min_id FROM domains) AS t)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domains DROP invitation_enabled, DROP invitation_limit');
    }
}
