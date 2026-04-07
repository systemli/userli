<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add invitation_waiting_period_days column to domains table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domains ADD invitation_waiting_period_days INT DEFAULT 7 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domains DROP invitation_waiting_period_days');
    }
}
