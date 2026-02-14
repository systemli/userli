<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260214120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add domain_id column to virtual_vouchers table';
    }

    public function up(Schema $schema): void
    {
        // Add nullable column first
        $this->addSql('ALTER TABLE virtual_vouchers ADD domain_id INT DEFAULT NULL');

        // Set existing vouchers to the default domain (lowest ID)
        $this->addSql('UPDATE virtual_vouchers SET domain_id = (SELECT MIN(id) FROM virtual_domains)');

        // Make column NOT NULL and add FK + index
        $this->addSql('ALTER TABLE virtual_vouchers CHANGE domain_id domain_id INT NOT NULL');
        $this->addSql('ALTER TABLE virtual_vouchers ADD CONSTRAINT FK_98F8AFBA115F0EE5 FOREIGN KEY (domain_id) REFERENCES virtual_domains (id)');
        $this->addSql('CREATE INDEX IDX_98F8AFBA115F0EE5 ON virtual_vouchers (domain_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE virtual_vouchers DROP FOREIGN KEY FK_98F8AFBA115F0EE5');
        $this->addSql('DROP INDEX IDX_98F8AFBA115F0EE5 ON virtual_vouchers');
        $this->addSql('ALTER TABLE virtual_vouchers DROP domain_id');
    }
}
