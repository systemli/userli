<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260306120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename openpgp_keys.user_id to uploader_id with SET NULL semantics';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE openpgp_keys DROP FOREIGN KEY FK_3DB259EAA76ED395');
        $this->addSql('DROP INDEX IDX_BAB4DF37A76ED395 ON openpgp_keys');
        $this->addSql('ALTER TABLE openpgp_keys CHANGE user_id uploader_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE openpgp_keys ADD CONSTRAINT FK_BAB4DF3716678C77 FOREIGN KEY (uploader_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_BAB4DF3716678C77 ON openpgp_keys (uploader_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE openpgp_keys DROP FOREIGN KEY FK_BAB4DF3716678C77');
        $this->addSql('DROP INDEX IDX_BAB4DF3716678C77 ON openpgp_keys');
        $this->addSql('ALTER TABLE openpgp_keys CHANGE uploader_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE openpgp_keys ADD CONSTRAINT FK_3DB259EAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_BAB4DF37A76ED395 ON openpgp_keys (user_id)');
    }
}
