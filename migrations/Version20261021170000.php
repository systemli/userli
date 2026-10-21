<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use DateTimeImmutable;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261021170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Disable twofactor_app_passwords_required for existing installations';
    }

    public function up(Schema $schema): void
    {
        // No schema change needed, the settings table already exists.
    }

    // New installations have no users yet and keep the default (enabled)
    public function postUp(Schema $schema): void
    {
        $hasUsers = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM users') > 0;
        $exists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM settings WHERE name = 'twofactor_app_passwords_required'") > 0;

        if ($hasUsers && !$exists) {
            $now = new DateTimeImmutable()->format('Y-m-d H:i:s');
            $this->connection->insert('settings', [
                'name' => 'twofactor_app_passwords_required',
                'value' => '0',
                'creation_time' => $now,
                'updated_time' => $now,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->connection->delete('settings', ['name' => 'twofactor_app_passwords_required']);
    }
}
