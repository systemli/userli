<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to add a notes column to the virtual_aliases table.
 */
final class Version20260131142312 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notes column to virtual_aliases table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE virtual_aliases ADD note VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE virtual_aliases DROP note');
    }
}
