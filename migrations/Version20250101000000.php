<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial schema migration that creates all tables.
 * Skips execution if the schema already exists (for existing installations).
 * Each table is created only if it doesn't already exist.
 */
final class Version20250101000000 extends AbstractMigration
{
    private AbstractSchemaManager $schemaManager;

    public function getDescription(): string
    {
        return 'Create initial database schema';
    }

    public function up(Schema $schema): void
    {
        $this->schemaManager = $this->connection->createSchemaManager();

        // Create tables without foreign key dependencies first
        $this->createTableIfNotExists('virtual_domains', 'CREATE TABLE virtual_domains (id INT AUTO_INCREMENT NOT NULL, creation_time DATETIME NOT NULL, name VARCHAR(255) NOT NULL, updated_time DATETIME NOT NULL, UNIQUE INDEX UNIQ_BA0C6C525E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->createTableIfNotExists('virtual_reserved_names', 'CREATE TABLE virtual_reserved_names (id INT AUTO_INCREMENT NOT NULL, creation_time DATETIME NOT NULL, name VARCHAR(255) NOT NULL, updated_time DATETIME NOT NULL, UNIQUE INDEX UNIQ_D44239F15E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->createTableIfNotExists('api_tokens', 'CREATE TABLE api_tokens (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, scopes JSON NOT NULL COMMENT \'(DC2Type:json)\', token VARCHAR(64) NOT NULL, creation_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_used_time DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_2CAD560E5F37A13B (token), INDEX idx_token (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->createTableIfNotExists('webhook_endpoints', 'CREATE TABLE webhook_endpoints (id INT AUTO_INCREMENT NOT NULL, url VARCHAR(2048) NOT NULL, secret VARCHAR(255) NOT NULL, events JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', enabled TINYINT(1) DEFAULT 1 NOT NULL, creation_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_E95677CC50F9BB84 (enabled), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->createTableIfNotExists('settings', 'CREATE TABLE settings (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, value LONGTEXT DEFAULT NULL, creation_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_time DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_SETTING_NAME (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->createTableIfNotExists('messenger_messages', 'CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create virtual_vouchers before virtual_users (because of invitation_voucher_id FK)
        $this->createTableIfNotExists('virtual_vouchers', 'CREATE TABLE virtual_vouchers (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, redeemed_time DATETIME DEFAULT NULL, code VARCHAR(255) NOT NULL, creation_time DATETIME NOT NULL, UNIQUE INDEX UNIQ_98F8AFBA77153098 (code), INDEX IDX_98F8AFBAA76ED395 (user_id), INDEX code_idx (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create virtual_users with LONGTEXT roles (before Version20260117155052 converts to JSON)
        $this->createTableIfNotExists('virtual_users', 'CREATE TABLE virtual_users (id INT AUTO_INCREMENT NOT NULL, domain_id INT NOT NULL, invitation_voucher_id INT DEFAULT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', password_change_required TINYINT(1) DEFAULT 0 NOT NULL, creation_time DATETIME NOT NULL, deleted TINYINT(1) DEFAULT 0 NOT NULL, email VARCHAR(255) NOT NULL, last_login_time DATETIME DEFAULT NULL, mail_crypt TINYINT(1) DEFAULT 0 NOT NULL, mail_crypt_public_key LONGTEXT DEFAULT NULL, mail_crypt_secret_box LONGTEXT DEFAULT NULL, password VARCHAR(255) NOT NULL, password_version INT NOT NULL, quota INT DEFAULT NULL, recovery_secret_box LONGTEXT DEFAULT NULL, recovery_start_time DATETIME DEFAULT NULL, totp_backup_codes LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', totp_secret VARCHAR(255) DEFAULT NULL, totp_confirmed TINYINT(1) DEFAULT 0 NOT NULL, updated_time DATETIME NOT NULL, UNIQUE INDEX UNIQ_3C68956AE7927C74 (email), INDEX IDX_3C68956A115F0EE5 (domain_id), UNIQUE INDEX UNIQ_3C68956AB29B3622 (invitation_voucher_id), INDEX email_idx (email), INDEX creation_time_idx (creation_time), INDEX deleted_idx (deleted), INDEX email_deleted_idx (email, deleted), INDEX domain_deleted_idx (domain_id, deleted), INDEX email_domain_idx (email, domain_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->createTableIfNotExists('user_notifications', 'CREATE TABLE user_notifications (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, type VARCHAR(50) NOT NULL, creation_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', metadata JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_8E8E1D83A76ED395 (user_id), INDEX idx_user_type_creation_time (user_id, type, creation_time), INDEX idx_user_type (user_id, type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->createTableIfNotExists('virtual_aliases', 'CREATE TABLE virtual_aliases (id INT AUTO_INCREMENT NOT NULL, domain_id INT DEFAULT NULL, user_id INT DEFAULT NULL, source VARCHAR(255) NOT NULL, destination VARCHAR(255) DEFAULT NULL, creation_time DATETIME NOT NULL, deleted TINYINT(1) DEFAULT 0 NOT NULL, random TINYINT(1) DEFAULT 0 NOT NULL, updated_time DATETIME NOT NULL, INDEX IDX_696568F6115F0EE5 (domain_id), INDEX IDX_696568F6A76ED395 (user_id), INDEX source_deleted_idx (source, deleted), INDEX destination_deleted_idx (destination, deleted), INDEX user_deleted_idx (user_id, deleted), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->createTableIfNotExists('webhook_deliveries', 'CREATE TABLE webhook_deliveries (id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', endpoint_id INT NOT NULL, type VARCHAR(100) NOT NULL, request_body JSON NOT NULL COMMENT \'(DC2Type:json)\', request_headers JSON NOT NULL COMMENT \'(DC2Type:json)\', response_code INT DEFAULT NULL, response_body LONGTEXT DEFAULT NULL, success TINYINT(1) DEFAULT 0 NOT NULL, error LONGTEXT DEFAULT NULL, attempts INT DEFAULT 0 NOT NULL, dispatched_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_time DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3681F32D21AF7E36 (endpoint_id), INDEX IDX_3681F32DA80E9988 (dispatched_time), INDEX IDX_3681F32DA80E99886F00DFB2 (dispatched_time, success), INDEX IDX_3681F32D6F00DFB2 (success), INDEX IDX_3681F32D8CDE5729 (type), INDEX IDX_3681F32D21AF7E368CDE5729 (endpoint_id, type), INDEX IDX_3681F32D21AF7E368CDE57296F00DFB2 (endpoint_id, type, success), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->createTableIfNotExists('virtual_openpgp_keys', 'CREATE TABLE virtual_openpgp_keys (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, email VARCHAR(255) NOT NULL, key_id LONGTEXT NOT NULL, key_fingerprint LONGTEXT NOT NULL, key_expire_time DATETIME DEFAULT NULL, key_data LONGTEXT NOT NULL, UNIQUE INDEX UNIQ_3DB259EAE7927C74 (email), INDEX IDX_3DB259EAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add foreign key constraints (only if both tables exist)
        $this->addForeignKeyIfNotExists('virtual_users', 'FK_3C68956A115F0EE5', 'ALTER TABLE virtual_users ADD CONSTRAINT FK_3C68956A115F0EE5 FOREIGN KEY (domain_id) REFERENCES virtual_domains (id)');
        $this->addForeignKeyIfNotExists('virtual_users', 'FK_3C68956AB29B3622', 'ALTER TABLE virtual_users ADD CONSTRAINT FK_3C68956AB29B3622 FOREIGN KEY (invitation_voucher_id) REFERENCES virtual_vouchers (id)');
        $this->addForeignKeyIfNotExists('user_notifications', 'FK_8E8E1D83A76ED395', 'ALTER TABLE user_notifications ADD CONSTRAINT FK_8E8E1D83A76ED395 FOREIGN KEY (user_id) REFERENCES virtual_users (id) ON DELETE CASCADE');
        $this->addForeignKeyIfNotExists('virtual_aliases', 'FK_696568F6115F0EE5', 'ALTER TABLE virtual_aliases ADD CONSTRAINT FK_696568F6115F0EE5 FOREIGN KEY (domain_id) REFERENCES virtual_domains (id)');
        $this->addForeignKeyIfNotExists('virtual_aliases', 'FK_696568F6A76ED395', 'ALTER TABLE virtual_aliases ADD CONSTRAINT FK_696568F6A76ED395 FOREIGN KEY (user_id) REFERENCES virtual_users (id)');
        $this->addForeignKeyIfNotExists('virtual_vouchers', 'FK_98F8AFBAA76ED395', 'ALTER TABLE virtual_vouchers ADD CONSTRAINT FK_98F8AFBAA76ED395 FOREIGN KEY (user_id) REFERENCES virtual_users (id)');
        $this->addForeignKeyIfNotExists('webhook_deliveries', 'FK_3681F32D21AF7E36', 'ALTER TABLE webhook_deliveries ADD CONSTRAINT FK_3681F32D21AF7E36 FOREIGN KEY (endpoint_id) REFERENCES webhook_endpoints (id) ON DELETE CASCADE');
        $this->addForeignKeyIfNotExists('virtual_openpgp_keys', 'FK_3DB259EAA76ED395', 'ALTER TABLE virtual_openpgp_keys ADD CONSTRAINT FK_3DB259EAA76ED395 FOREIGN KEY (user_id) REFERENCES virtual_users (id)');
    }

    private function createTableIfNotExists(string $tableName, string $sql): void
    {
        if (!$this->schemaManager->tablesExist([$tableName])) {
            $this->addSql($sql);
        } else {
            $this->write(sprintf('Table "%s" already exists, skipping.', $tableName));
        }
    }

    private function addForeignKeyIfNotExists(string $tableName, string $foreignKeyName, string $sql): void
    {
        if (!$this->schemaManager->tablesExist([$tableName])) {
            return;
        }

        $foreignKeys = $this->schemaManager->listTableForeignKeys($tableName);
        foreach ($foreignKeys as $foreignKey) {
            if ($foreignKey->getName() === $foreignKeyName) {
                $this->write(sprintf('Foreign key "%s" on table "%s" already exists, skipping.', $foreignKeyName, $tableName));

                return;
            }
        }

        $this->addSql($sql);
    }

    public function down(Schema $schema): void
    {
        // Drop foreign keys first
        $this->addSql('ALTER TABLE virtual_users DROP FOREIGN KEY FK_3C68956A115F0EE5');
        $this->addSql('ALTER TABLE virtual_users DROP FOREIGN KEY FK_3C68956AB29B3622');
        $this->addSql('ALTER TABLE user_notifications DROP FOREIGN KEY FK_8E8E1D83A76ED395');
        $this->addSql('ALTER TABLE virtual_aliases DROP FOREIGN KEY FK_696568F6115F0EE5');
        $this->addSql('ALTER TABLE virtual_aliases DROP FOREIGN KEY FK_696568F6A76ED395');
        $this->addSql('ALTER TABLE virtual_vouchers DROP FOREIGN KEY FK_98F8AFBAA76ED395');
        $this->addSql('ALTER TABLE webhook_deliveries DROP FOREIGN KEY FK_3681F32D21AF7E36');
        $this->addSql('ALTER TABLE virtual_openpgp_keys DROP FOREIGN KEY FK_3DB259EAA76ED395');

        // Drop all tables
        $this->addSql('DROP TABLE virtual_openpgp_keys');
        $this->addSql('DROP TABLE webhook_deliveries');
        $this->addSql('DROP TABLE virtual_aliases');
        $this->addSql('DROP TABLE user_notifications');
        $this->addSql('DROP TABLE virtual_users');
        $this->addSql('DROP TABLE virtual_vouchers');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP TABLE settings');
        $this->addSql('DROP TABLE webhook_endpoints');
        $this->addSql('DROP TABLE api_tokens');
        $this->addSql('DROP TABLE virtual_reserved_names');
        $this->addSql('DROP TABLE virtual_domains');
    }
}
