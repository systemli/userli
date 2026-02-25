<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Tuupola\Base32;

final class Version20260222120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add wkd_hash column to virtual_openpgp_keys and backfill from email local parts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE virtual_openpgp_keys ADD wkd_hash VARCHAR(32) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_wkd_hash ON virtual_openpgp_keys (wkd_hash)');
    }

    public function postUp(Schema $schema): void
    {
        $rows = $this->connection->fetchAllAssociative('SELECT id, email FROM virtual_openpgp_keys WHERE wkd_hash IS NULL');

        $base32Encoder = new Base32(['characters' => Base32::ZBASE32]);

        foreach ($rows as $row) {
            [$localPart] = explode('@', (string) $row['email']);
            $wkdHash = $base32Encoder->encode(sha1(strtolower($localPart), true));

            $this->connection->executeStatement(
                'UPDATE virtual_openpgp_keys SET wkd_hash = :hash WHERE id = :id',
                ['hash' => $wkdHash, 'id' => $row['id']],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_wkd_hash ON virtual_openpgp_keys');
        $this->addSql('ALTER TABLE virtual_openpgp_keys DROP wkd_hash');
    }
}
