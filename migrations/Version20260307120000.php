<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add mail_crypt setting from environment variable';
    }

    public function up(Schema $schema): void
    {
        // No schema change needed — the settings table already exists.
    }

    public function postUp(Schema $schema): void
    {
        $value = $_ENV['MAIL_CRYPT'] ?? getenv('MAIL_CRYPT') ?: '2';

        $exists = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM settings WHERE name = 'mail_crypt'"
        );

        if ((int) $exists === 0) {
            $this->connection->insert('settings', [
                'name' => 'mail_crypt',
                'value' => (string) $value,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->connection->delete('settings', ['name' => 'mail_crypt']);
    }
}
