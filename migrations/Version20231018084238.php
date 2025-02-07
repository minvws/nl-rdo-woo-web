<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231018084238 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE inventory_process_run (id UUID NOT NULL, dossier_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, generic_errors JSON NOT NULL, row_errors JSON NOT NULL, status VARCHAR(255) NOT NULL, progress SMALLINT NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1846874C611C0C56 ON inventory_process_run (dossier_id)');
        $this->addSql('COMMENT ON COLUMN inventory_process_run.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN inventory_process_run.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN inventory_process_run.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN inventory_process_run.started_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN inventory_process_run.ended_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE inventory_process_run ADD CONSTRAINT FK_1846874C611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory_process_run DROP CONSTRAINT FK_1846874C611C0C56');
        $this->addSql('DROP TABLE inventory_process_run');
    }
}
