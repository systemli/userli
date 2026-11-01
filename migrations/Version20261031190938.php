<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261031190938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add webauthn_credentials and webauthn_challenges tables for WebAuthn as second factor';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE webauthn_credentials (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(64) NOT NULL, credential_id LONGTEXT NOT NULL, credential_id_hash VARCHAR(64) NOT NULL, public_key LONGTEXT NOT NULL, user_handle VARCHAR(255) NOT NULL, sign_count INT UNSIGNED DEFAULT 0 NOT NULL, transports JSON NOT NULL, aaguid VARCHAR(36) NOT NULL, attestation_type VARCHAR(32) NOT NULL, backup_eligible TINYINT DEFAULT NULL, backup_status TINYINT DEFAULT NULL, uv_initialized TINYINT DEFAULT NULL, last_used_time DATETIME DEFAULT NULL, creation_time DATETIME NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_WEBAUTHN_CREDENTIAL_ID_HASH (credential_id_hash), UNIQUE INDEX UNIQ_WEBAUTHN_CREDENTIAL_USER_NAME (user_id, name), INDEX IDX_DFEA8490A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE webauthn_credentials ADD CONSTRAINT FK_DFEA8490A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users ADD webauthn_user_handle VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE TABLE webauthn_challenges (id INT AUTO_INCREMENT NOT NULL, challenge_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_WEBAUTHN_CHALLENGE_EXPIRES_AT (expires_at), UNIQUE INDEX UNIQ_WEBAUTHN_CHALLENGE_HASH (challenge_hash), INDEX IDX_87CB9952A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE webauthn_challenges ADD CONSTRAINT FK_87CB9952A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE webauthn_challenges DROP FOREIGN KEY FK_87CB9952A76ED395');
        $this->addSql('DROP TABLE webauthn_challenges');
        $this->addSql('ALTER TABLE webauthn_credentials DROP FOREIGN KEY FK_DFEA8490A76ED395');
        $this->addSql('DROP TABLE webauthn_credentials');
        $this->addSql('ALTER TABLE users DROP webauthn_user_handle');
    }
}
