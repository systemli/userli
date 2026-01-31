<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add indexes to webhook_deliveries for endpoint_id and type.
 */
final class Version20260131181339 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes to webhook_deliveries for endpoint_id and type';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $indexes = $schemaManager->listTableIndexes('webhook_deliveries');

        if (isset($indexes['idx_3681f32d21af7e368cde5729'])) {
            $this->write('Index IDX_3681F32D21AF7E368CDE5729 already exists, skipping creation.');
        } else {
            $this->addSql('CREATE INDEX IDX_3681F32D21AF7E368CDE5729 ON webhook_deliveries (endpoint_id, type)');
        }

        if (isset($indexes['idx_3681f32d21af7e368cde57296f00dfb2'])) {
            $this->write('Index IDX_3681F32D21AF7E368CDE57296F00DFB2 already exists, skipping creation.');
        } else {
            $this->addSql('CREATE INDEX IDX_3681F32D21AF7E368CDE57296F00DFB2 ON webhook_deliveries (endpoint_id, type, success)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_3681F32D21AF7E368CDE5729 ON webhook_deliveries');
        $this->addSql('DROP INDEX IDX_3681F32D21AF7E368CDE57296F00DFB2 ON webhook_deliveries');
    }
}
