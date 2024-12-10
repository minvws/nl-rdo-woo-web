<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241113105317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tables for DocumentFileSet';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE document_file_set (id UUID NOT NULL, dossier_id UUID NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_46965A1C611C0C56 ON document_file_set (dossier_id)');
        $this->addSql('COMMENT ON COLUMN document_file_set.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN document_file_set.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN document_file_set.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN document_file_set.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE document_file_update (id UUID NOT NULL, document_file_set_id UUID NOT NULL, document_id UUID NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A29B24AD2A451CCD ON document_file_update (document_file_set_id)');
        $this->addSql('CREATE INDEX IDX_A29B24ADC33F7837 ON document_file_update (document_id)');
        $this->addSql('COMMENT ON COLUMN document_file_update.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN document_file_update.document_file_set_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN document_file_update.document_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN document_file_update.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN document_file_update.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE document_file_upload (id UUID NOT NULL, document_file_set_id UUID NOT NULL, status VARCHAR(255) NOT NULL, error VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_hash VARCHAR(128) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2D03F7CA2A451CCD ON document_file_upload (document_file_set_id)');
        $this->addSql('COMMENT ON COLUMN document_file_upload.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN document_file_upload.document_file_set_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN document_file_upload.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN document_file_upload.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE document_file_set ADD CONSTRAINT FK_46965A1C611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document_file_update ADD CONSTRAINT FK_A29B24AD2A451CCD FOREIGN KEY (document_file_set_id) REFERENCES document_file_set (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document_file_update ADD CONSTRAINT FK_A29B24ADC33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document_file_upload ADD CONSTRAINT FK_2D03F7CA2A451CCD FOREIGN KEY (document_file_set_id) REFERENCES document_file_set (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX uniq_ae096b07611c0c56 RENAME TO UNIQ_B8FE3D9611C0C56');
        $this->addSql('ALTER INDEX uniq_1846874c611c0c56 RENAME TO UNIQ_D8900FF7611C0C56');
        $this->addSql('CREATE UNIQUE INDEX unique_document_for_set ON document_file_update (document_file_set_id, document_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX unique_document_for_set');
        $this->addSql('ALTER TABLE document_file_set DROP CONSTRAINT FK_46965A1C611C0C56');
        $this->addSql('ALTER TABLE document_file_update DROP CONSTRAINT FK_A29B24AD2A451CCD');
        $this->addSql('ALTER TABLE document_file_update DROP CONSTRAINT FK_A29B24ADC33F7837');
        $this->addSql('ALTER TABLE document_file_upload DROP CONSTRAINT FK_2D03F7CA2A451CCD');
        $this->addSql('DROP TABLE document_file_set');
        $this->addSql('DROP TABLE document_file_update');
        $this->addSql('DROP TABLE document_file_upload');
        $this->addSql('ALTER INDEX uniq_b8fe3d9611c0c56 RENAME TO uniq_ae096b07611c0c56');
        $this->addSql('ALTER INDEX uniq_d8900ff7611c0c56 RENAME TO uniq_1846874c611c0c56');
    }
}
