<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to convert totp_backup_codes column from PHP serialized array to JSON.
 */
final class Version20260131182000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert totp_backup_codes column from PHP serialized array to JSON';
    }

    public function up(Schema $schema): void
    {
        // Fetch all users with their current totp_backup_codes
        $users = $this->connection->fetchAllAssociative('SELECT id, totp_backup_codes FROM virtual_users');

        foreach ($users as $user) {
            $codes = @unserialize($user['totp_backup_codes']);
            if ($codes === false) {
                // Already JSON or empty, try to decode as JSON
                $codes = json_decode($user['totp_backup_codes'], true);
                if ($codes === null) {
                    $codes = [];
                }
            }

            $this->connection->executeStatement(
                'UPDATE virtual_users SET totp_backup_codes = :codes WHERE id = :id',
                ['codes' => json_encode(array_values($codes)), 'id' => $user['id']]
            );
        }

        // Change column type
        $this->addSql('ALTER TABLE virtual_users CHANGE totp_backup_codes totp_backup_codes JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Fetch all users with their current totp_backup_codes
        $users = $this->connection->fetchAllAssociative('SELECT id, totp_backup_codes FROM virtual_users');

        foreach ($users as $user) {
            $codes = json_decode($user['totp_backup_codes'], true) ?? [];

            $this->connection->executeStatement(
                'UPDATE virtual_users SET totp_backup_codes = :codes WHERE id = :id',
                ['codes' => serialize($codes), 'id' => $user['id']]
            );
        }

        // Change column type back
        $this->addSql('ALTER TABLE virtual_users CHANGE totp_backup_codes totp_backup_codes LONGTEXT NOT NULL DEFAULT \'a:0:{}\' COMMENT \'(DC2Type:array)\'');
    }
}
