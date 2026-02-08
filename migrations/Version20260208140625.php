<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208140625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove Doctrine DBAL 3 type comments (DC2Type) from columns after upgrade to DBAL 4';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_tokens CHANGE scopes scopes JSON NOT NULL, CHANGE creation_time creation_time DATETIME NOT NULL, CHANGE last_used_time last_used_time DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE settings CHANGE creation_time creation_time DATETIME NOT NULL, CHANGE updated_time updated_time DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user_notifications CHANGE creation_time creation_time DATETIME NOT NULL, CHANGE metadata metadata JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE virtual_aliases CHANGE creation_time creation_time DATETIME NOT NULL, CHANGE updated_time updated_time DATETIME NOT NULL');
        $this->addSql('ALTER TABLE virtual_domains CHANGE creation_time creation_time DATETIME NOT NULL, CHANGE updated_time updated_time DATETIME NOT NULL');
        $this->addSql('ALTER TABLE virtual_openpgp_keys CHANGE key_expire_time key_expire_time DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE virtual_reserved_names CHANGE creation_time creation_time DATETIME NOT NULL, CHANGE updated_time updated_time DATETIME NOT NULL');
        $this->addSql('ALTER TABLE virtual_users CHANGE creation_time creation_time DATETIME NOT NULL, CHANGE last_login_time last_login_time DATETIME DEFAULT NULL, CHANGE recovery_start_time recovery_start_time DATETIME DEFAULT NULL, CHANGE updated_time updated_time DATETIME NOT NULL');
        $this->addSql('ALTER TABLE virtual_vouchers CHANGE redeemed_time redeemed_time DATETIME DEFAULT NULL, CHANGE creation_time creation_time DATETIME NOT NULL');
        $this->addSql('ALTER TABLE webhook_deliveries CHANGE id id BINARY(16) NOT NULL, CHANGE request_body request_body JSON NOT NULL, CHANGE request_headers request_headers JSON NOT NULL, CHANGE dispatched_time dispatched_time DATETIME NOT NULL, CHANGE delivered_time delivered_time DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE webhook_endpoints CHANGE events events JSON DEFAULT NULL, CHANGE creation_time creation_time DATETIME NOT NULL, CHANGE updated_time updated_time DATETIME NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL, CHANGE available_at available_at DATETIME NOT NULL, CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_tokens CHANGE scopes scopes JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE creation_time creation_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE last_used_time last_used_time DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE available_at available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE settings CHANGE creation_time creation_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_time updated_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_notifications CHANGE creation_time creation_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE metadata metadata JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE virtual_aliases CHANGE creation_time creation_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_time updated_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE virtual_domains CHANGE creation_time creation_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_time updated_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE virtual_openpgp_keys CHANGE key_expire_time key_expire_time DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE virtual_reserved_names CHANGE creation_time creation_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_time updated_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE virtual_users CHANGE creation_time creation_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE last_login_time last_login_time DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE recovery_start_time recovery_start_time DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_time updated_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE virtual_vouchers CHANGE redeemed_time redeemed_time DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE creation_time creation_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE webhook_deliveries CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:ulid)\', CHANGE request_body request_body JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE request_headers request_headers JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE dispatched_time dispatched_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE delivered_time delivered_time DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE webhook_endpoints CHANGE events events JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE creation_time creation_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_time updated_time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
