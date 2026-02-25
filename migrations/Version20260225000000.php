<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to remove the virtual_ prefix from table names.
 */
final class Version20260225000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename tables to remove the virtual_ prefix';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE virtual_aliases TO aliases');
        $this->addSql('RENAME TABLE virtual_domains TO domains');
        $this->addSql('RENAME TABLE virtual_openpgp_keys TO openpgp_keys');
        $this->addSql('RENAME TABLE virtual_reserved_names TO reserved_names');
        $this->addSql('RENAME TABLE virtual_users TO users');
        $this->addSql('RENAME TABLE virtual_vouchers TO vouchers');

        // Rename auto-generated indexes to match new table names
        $this->addSql('ALTER TABLE aliases RENAME INDEX idx_696568f6115f0ee5 TO IDX_5F12BB39115F0EE5');
        $this->addSql('ALTER TABLE aliases RENAME INDEX idx_696568f6a76ed395 TO IDX_5F12BB39A76ED395');
        $this->addSql('ALTER TABLE domains RENAME INDEX uniq_ba0c6c525e237e06 TO UNIQ_8C7BBF9D5E237E06');
        $this->addSql('ALTER TABLE openpgp_keys RENAME INDEX uniq_3db259eae7927c74 TO UNIQ_BAB4DF37E7927C74');
        $this->addSql('ALTER TABLE openpgp_keys RENAME INDEX idx_3db259eaa76ed395 TO IDX_BAB4DF37A76ED395');
        $this->addSql('ALTER TABLE reserved_names RENAME INDEX uniq_d44239f15e237e06 TO UNIQ_E40F23B05E237E06');
        $this->addSql('ALTER TABLE users RENAME INDEX uniq_3c68956ae7927c74 TO UNIQ_1483A5E9E7927C74');
        $this->addSql('ALTER TABLE users RENAME INDEX idx_3c68956a115f0ee5 TO IDX_1483A5E9115F0EE5');
        $this->addSql('ALTER TABLE users RENAME INDEX uniq_3c68956ab29b3622 TO UNIQ_1483A5E9B29B3622');
        $this->addSql('ALTER TABLE vouchers RENAME INDEX uniq_98f8afba77153098 TO UNIQ_9315074877153098');
        $this->addSql('ALTER TABLE vouchers RENAME INDEX idx_98f8afbaa76ed395 TO IDX_93150748A76ED395');
    }

    public function down(Schema $schema): void
    {
        // Restore original auto-generated index names before renaming tables
        $this->addSql('ALTER TABLE aliases RENAME INDEX IDX_5F12BB39115F0EE5 TO idx_696568f6115f0ee5');
        $this->addSql('ALTER TABLE aliases RENAME INDEX IDX_5F12BB39A76ED395 TO idx_696568f6a76ed395');
        $this->addSql('ALTER TABLE domains RENAME INDEX UNIQ_8C7BBF9D5E237E06 TO uniq_ba0c6c525e237e06');
        $this->addSql('ALTER TABLE openpgp_keys RENAME INDEX UNIQ_BAB4DF37E7927C74 TO uniq_3db259eae7927c74');
        $this->addSql('ALTER TABLE openpgp_keys RENAME INDEX IDX_BAB4DF37A76ED395 TO idx_3db259eaa76ed395');
        $this->addSql('ALTER TABLE reserved_names RENAME INDEX UNIQ_E40F23B05E237E06 TO uniq_d44239f15e237e06');
        $this->addSql('ALTER TABLE users RENAME INDEX UNIQ_1483A5E9E7927C74 TO uniq_3c68956ae7927c74');
        $this->addSql('ALTER TABLE users RENAME INDEX IDX_1483A5E9115F0EE5 TO idx_3c68956a115f0ee5');
        $this->addSql('ALTER TABLE users RENAME INDEX UNIQ_1483A5E9B29B3622 TO uniq_3c68956ab29b3622');
        $this->addSql('ALTER TABLE vouchers RENAME INDEX UNIQ_9315074877153098 TO uniq_98f8afba77153098');
        $this->addSql('ALTER TABLE vouchers RENAME INDEX IDX_93150748A76ED395 TO idx_98f8afbaa76ed395');

        $this->addSql('RENAME TABLE aliases TO virtual_aliases');
        $this->addSql('RENAME TABLE domains TO virtual_domains');
        $this->addSql('RENAME TABLE openpgp_keys TO virtual_openpgp_keys');
        $this->addSql('RENAME TABLE reserved_names TO virtual_reserved_names');
        $this->addSql('RENAME TABLE users TO virtual_users');
        $this->addSql('RENAME TABLE vouchers TO virtual_vouchers');
    }
}
