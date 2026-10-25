<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Keep main passwords working for mail clients of 2FA users on existing installations';
    }

    public function up(Schema $schema): void
    {
        // New installations have no users yet and get the default (on)
        if (false === $this->connection->fetchOne('SELECT id FROM users LIMIT 1')) {
            return;
        }

        $this->addSql("INSERT IGNORE INTO settings (name, value, creation_time, updated_time) VALUES ('twofactor_app_passwords_required', '0', NOW(), NOW())");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM settings WHERE name = 'twofactor_app_passwords_required'");
    }
}
