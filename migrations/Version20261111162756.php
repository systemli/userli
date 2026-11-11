<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261111162756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add announcements table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE announcements (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL, recipient_count INT NOT NULL, status VARCHAR(20) NOT NULL, last_queued_user_id INT NOT NULL, sent_count INT NOT NULL, not_sent_count INT NOT NULL, creation_time DATETIME NOT NULL, author_id INT DEFAULT NULL, domain_id INT NOT NULL, INDEX IDX_F422A9DF675F31B (author_id), INDEX IDX_F422A9D115F0EE5 (domain_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE announcements ADD CONSTRAINT FK_F422A9DF675F31B FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE announcements ADD CONSTRAINT FK_F422A9D115F0EE5 FOREIGN KEY (domain_id) REFERENCES domains (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE announcements DROP FOREIGN KEY FK_F422A9DF675F31B');
        $this->addSql('ALTER TABLE announcements DROP FOREIGN KEY FK_F422A9D115F0EE5');
        $this->addSql('DROP TABLE announcements');
    }
}
