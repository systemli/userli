<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate postmaster aliases to settings and ensure reserved names exist';
    }

    public function up(Schema $schema): void
    {
        // Migrate destination of first postmaster alias to setting
        $destination = $this->connection->fetchOne(
            "SELECT destination FROM aliases WHERE source LIKE 'postmaster@%' AND deleted = 0 LIMIT 1"
        );

        if (false !== $destination && '' !== $destination) {
            $this->connection->executeStatement(
                'INSERT INTO settings (name, value) VALUES (:name, :value) ON DUPLICATE KEY UPDATE value = :value',
                ['name' => 'postmaster_address', 'value' => $destination]
            );
        }

        // Delete all postmaster aliases
        $this->addSql("DELETE FROM aliases WHERE source LIKE 'postmaster@%'");

        // Ensure reserved names exist
        foreach (['postmaster', 'abuse'] as $name) {
            $exists = $this->connection->fetchOne(
                'SELECT id FROM reserved_names WHERE name = :name',
                ['name' => $name]
            );

            if (false === $exists) {
                $this->connection->executeStatement(
                    'INSERT INTO reserved_names (name, created_time) VALUES (:name, NOW())',
                    ['name' => $name]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM settings WHERE name IN ('postmaster_address', 'abuse_address')");
    }
}
