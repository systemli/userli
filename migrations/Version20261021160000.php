<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261021160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add expires_at to app_passwords';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_passwords ADD expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_passwords DROP expires_at');
    }
}
