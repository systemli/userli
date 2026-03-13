<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add domain_id to openpgp_keys table for domain-scoped filtering';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE openpgp_keys ADD domain_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE openpgp_keys ADD CONSTRAINT FK_openpgp_keys_domain FOREIGN KEY (domain_id) REFERENCES domains (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_openpgp_keys_domain ON openpgp_keys (domain_id)');
    }

    public function postUp(Schema $schema): void
    {
        // Populate domain_id from the email column for existing keys
        $this->connection->executeStatement(
            'UPDATE openpgp_keys SET domain_id = (SELECT d.id FROM domains d WHERE d.name = SUBSTRING(openpgp_keys.email, LOCATE(\'@\', openpgp_keys.email) + 1)) WHERE domain_id IS NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE openpgp_keys DROP FOREIGN KEY FK_openpgp_keys_domain');
        $this->addSql('DROP INDEX IDX_openpgp_keys_domain ON openpgp_keys');
        $this->addSql('ALTER TABLE openpgp_keys DROP domain_id');
    }
}
