<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Convert all datetime columns to datetime_immutable type.
 */
final class Version20260206211604 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert all datetime columns to datetime_immutable type';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE virtual_aliases CHANGE creation_time creation_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_time updated_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE virtual_domains CHANGE creation_time creation_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_time updated_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE virtual_openpgp_keys CHANGE key_expire_time key_expire_time DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE virtual_reserved_names CHANGE creation_time creation_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_time updated_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE virtual_users CHANGE creation_time creation_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE last_login_time last_login_time DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE recovery_start_time recovery_start_time DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_time updated_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE virtual_vouchers CHANGE redeemed_time redeemed_time DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE creation_time creation_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE virtual_domains CHANGE creation_time creation_time DATETIME NOT NULL, CHANGE updated_time updated_time DATETIME NOT NULL');
        $this->addSql('ALTER TABLE virtual_reserved_names CHANGE creation_time creation_time DATETIME NOT NULL, CHANGE updated_time updated_time DATETIME NOT NULL');
        $this->addSql('ALTER TABLE virtual_aliases CHANGE creation_time creation_time DATETIME NOT NULL, CHANGE updated_time updated_time DATETIME NOT NULL');
        $this->addSql('ALTER TABLE virtual_vouchers CHANGE redeemed_time redeemed_time DATETIME DEFAULT NULL, CHANGE creation_time creation_time DATETIME NOT NULL');
        $this->addSql('ALTER TABLE virtual_openpgp_keys CHANGE key_expire_time key_expire_time DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE virtual_users CHANGE creation_time creation_time DATETIME NOT NULL, CHANGE last_login_time last_login_time DATETIME DEFAULT NULL, CHANGE recovery_start_time recovery_start_time DATETIME DEFAULT NULL, CHANGE updated_time updated_time DATETIME NOT NULL');
    }
}
