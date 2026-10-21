<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261021131007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add last_login_time to app_passwords';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_passwords ADD last_login_time DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_passwords DROP last_login_time');
    }
}
