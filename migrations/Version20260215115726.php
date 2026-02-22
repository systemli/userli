<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260215115726 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add smtp_quota_limits column to virtual_users and virtual_aliases tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE virtual_aliases ADD smtp_quota_limits JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE virtual_users ADD smtp_quota_limits JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE virtual_aliases DROP smtp_quota_limits');
        $this->addSql('ALTER TABLE virtual_users DROP smtp_quota_limits');
    }
}
