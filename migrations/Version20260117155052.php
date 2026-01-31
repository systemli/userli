<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Enum\Roles;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to convert roles column from PHP serialized array to JSON.
 */
final class Version20260117155052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert roles column from PHP serialized array to JSON';
    }

    public function up(Schema $schema): void
    {
        // Fetch all users with their current roles
        $users = $this->connection->fetchAllAssociative('SELECT id, roles FROM virtual_users');

        foreach ($users as $user) {
            $roles = @unserialize($user['roles']);
            if ($roles === false) {
                // Already JSON or empty, try to decode as JSON
                $roles = json_decode($user['roles'], true);
                if ($roles === null) {
                    $roles = [Roles::USER];
                }
            }

            $this->connection->executeStatement(
                'UPDATE virtual_users SET roles = :roles WHERE id = :id',
                ['roles' => json_encode(array_values($roles)), 'id' => $user['id']]
            );
        }

        // Change column type and remove default
        $this->addSql('ALTER TABLE virtual_users CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Fetch all users with their current roles
        $users = $this->connection->fetchAllAssociative('SELECT id, roles FROM virtual_users');

        foreach ($users as $user) {
            $roles = json_decode($user['roles'], true) ?? [];

            $this->connection->executeStatement(
                'UPDATE virtual_users SET roles = :roles WHERE id = :id',
                ['roles' => serialize($roles), 'id' => $user['id']]
            );
        }

        // Change column type back
        $this->addSql('ALTER TABLE virtual_users CHANGE roles roles LONGTEXT NOT NULL DEFAULT \'a:0:{}\' COMMENT \'(DC2Type:array)\'');
    }
}
