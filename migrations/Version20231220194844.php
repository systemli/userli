<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add enabled field to User entity.
 */
final class Version20231220194844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add enabled field to User entity.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->getTable('virtual_users')->hasColumn('enabled')) {
            return;
        }

        $this->addSql('ALTER TABLE virtual_users ADD enabled TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->getTable('virtual_users')->hasColumn('enabled')) {
            return;
        }

        $this->addSql('ALTER TABLE virtual_users DROP enabled');
    }
}
