<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260228120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create webhook_endpoint_domain join table for domain filtering on webhooks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE webhook_endpoint_domain (webhook_endpoint_id INT NOT NULL, domain_id INT NOT NULL, INDEX IDX_CA3914A227404D5F (webhook_endpoint_id), INDEX IDX_CA3914A2115F0EE5 (domain_id), PRIMARY KEY(webhook_endpoint_id, domain_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE webhook_endpoint_domain ADD CONSTRAINT FK_CA3914A227404D5F FOREIGN KEY (webhook_endpoint_id) REFERENCES webhook_endpoints (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE webhook_endpoint_domain ADD CONSTRAINT FK_CA3914A2115F0EE5 FOREIGN KEY (domain_id) REFERENCES domains (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE webhook_endpoint_domain DROP FOREIGN KEY FK_CA3914A227404D5F');
        $this->addSql('ALTER TABLE webhook_endpoint_domain DROP FOREIGN KEY FK_CA3914A2115F0EE5');
        $this->addSql('DROP TABLE webhook_endpoint_domain');
    }
}
