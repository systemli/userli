<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add domain_id column to vouchers table';
    }

    public function up(Schema $schema): void
    {
        // Add nullable column first
        $this->addSql('ALTER TABLE vouchers ADD domain_id INT DEFAULT NULL');

        // Set existing vouchers to the default domain (lowest ID)
        $this->addSql('UPDATE vouchers SET domain_id = (SELECT MIN(id) FROM domains)');

        // Make column NOT NULL and add FK + index
        $this->addSql('ALTER TABLE vouchers CHANGE domain_id domain_id INT NOT NULL');
        $this->addSql('ALTER TABLE vouchers ADD CONSTRAINT FK_93150748115F0EE5 FOREIGN KEY (domain_id) REFERENCES domains (id)');
        $this->addSql('CREATE INDEX IDX_93150748115F0EE5 ON vouchers (domain_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vouchers DROP FOREIGN KEY FK_93150748115F0EE5');
        $this->addSql('DROP INDEX IDX_93150748115F0EE5 ON vouchers');
        $this->addSql('ALTER TABLE vouchers DROP domain_id');
    }
}
