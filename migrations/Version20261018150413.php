<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261018150413 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add app_passwords table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_passwords (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(64) NOT NULL, hash VARCHAR(64) NOT NULL, mail_crypt_secret_box LONGTEXT NOT NULL, creation_time DATETIME NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_45ED7423D1B862B8 (hash), UNIQUE INDEX UNIQ_APP_PASSWORD_USER_NAME (user_id, name), INDEX IDX_45ED7423A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE app_passwords ADD CONSTRAINT FK_45ED7423A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_passwords DROP FOREIGN KEY FK_45ED7423A76ED395');
        $this->addSql('DROP TABLE app_passwords');
    }
}
