<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261116160934 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add expiry_notified_at column to openpgp_keys table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE openpgp_keys ADD expiry_notified_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE openpgp_keys DROP expiry_notified_at');
    }
}
