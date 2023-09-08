<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230814120620 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE decision_document (id UUID NOT NULL, dossier_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(255) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_55B54548611C0C56 ON decision_document (dossier_id)');
        $this->addSql('COMMENT ON COLUMN decision_document.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN decision_document.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN decision_document.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN decision_document.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE inventory (id UUID NOT NULL, dossier_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(255) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B12D4A36611C0C56 ON inventory (dossier_id)');
        $this->addSql('COMMENT ON COLUMN inventory.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN inventory.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN inventory.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN inventory.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE decision_document ADD CONSTRAINT FK_55B54548611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE inventory ADD CONSTRAINT FK_B12D4A36611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("INSERT INTO inventory(id, dossier_id, created_at, updated_at, file_mimetype, file_path, file_size, file_type, file_source_type, file_name, file_uploaded)
                                SELECT d.id, dd.dossier_id, d.created_at, d.updated_at, d.mimetype, d.filepath, d.filesize, d.file_type, d.source_type, d.filename, d.uploaded
                                FROM document d
                                JOIN document_dossier dd ON dd.document_id = d.id
                                WHERE d.class='inventory'");
        $this->addSql("DELETE FROM document WHERE class='inventory'");

        $this->addSql("INSERT INTO decision_document(id, dossier_id, created_at, updated_at, file_mimetype, file_path, file_size, file_type, file_source_type, file_name, file_uploaded)
                                SELECT d.id, dd.dossier_id, d.created_at, d.updated_at, d.mimetype, d.filepath, d.filesize, d.file_type, d.source_type, d.filename, d.uploaded
                                FROM document d
                                JOIN document_dossier dd ON dd.document_id = d.id
                                WHERE d.class='decision'");
        $this->addSql("DELETE FROM document WHERE class='decision'");

        $this->addSql('ALTER TABLE document DROP class');
        $this->addSql('ALTER TABLE document ALTER file_type DROP NOT NULL');
        $this->addSql('ALTER TABLE document RENAME COLUMN filename TO file_name');
        $this->addSql('ALTER TABLE document RENAME COLUMN source_type TO file_source_type');
        $this->addSql('ALTER TABLE document RENAME COLUMN mimetype TO file_mimetype');
        $this->addSql('ALTER TABLE document RENAME COLUMN filepath TO file_path');
        $this->addSql('ALTER TABLE document RENAME COLUMN filesize TO file_size');
        $this->addSql('ALTER TABLE document RENAME COLUMN uploaded TO file_uploaded');
        $this->addSql('ALTER TABLE dossier ADD inventory_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE dossier ADD decision_document_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN dossier.inventory_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN dossier.decision_document_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E0379EEA759 FOREIGN KEY (inventory_id) REFERENCES inventory (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E0372ECDE55E FOREIGN KEY (decision_document_id) REFERENCES decision_document (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3D48E0379EEA759 ON dossier (inventory_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3D48E0372ECDE55E ON dossier (decision_document_id)');
        $this->addSql('ALTER TABLE inquiry_dossier DROP CONSTRAINT inquiry_dossier_pkey');
        $this->addSql('ALTER TABLE inquiry_dossier ADD PRIMARY KEY (dossier_id, inquiry_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE dossier DROP CONSTRAINT FK_3D48E0372ECDE55E');
        $this->addSql('ALTER TABLE dossier DROP CONSTRAINT FK_3D48E0379EEA759');
        $this->addSql('ALTER TABLE decision_document DROP CONSTRAINT FK_55B54548611C0C56');
        $this->addSql('ALTER TABLE inventory DROP CONSTRAINT FK_B12D4A36611C0C56');
        $this->addSql('DROP TABLE decision_document');
        $this->addSql('DROP TABLE inventory');
        $this->addSql('DROP INDEX UNIQ_3D48E0379EEA759');
        $this->addSql('DROP INDEX UNIQ_3D48E0372ECDE55E');
        $this->addSql('ALTER TABLE dossier DROP inventory_id');
        $this->addSql('ALTER TABLE dossier DROP decision_document_id');
        $this->addSql('DROP INDEX inquiry_dossier_pkey');
        $this->addSql('ALTER TABLE inquiry_dossier ADD PRIMARY KEY (inquiry_id, dossier_id)');
        $this->addSql('ALTER TABLE batch_download ALTER status SET DEFAULT \'\'');
        $this->addSql('ALTER TABLE document ADD filename VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE document ADD source_type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE document ADD class VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE document DROP file_source_type');
        $this->addSql('ALTER TABLE document DROP file_name');
        $this->addSql('ALTER TABLE document ALTER suspended SET DEFAULT false');
        $this->addSql('ALTER TABLE document ALTER withdrawn SET DEFAULT false');
        $this->addSql('ALTER TABLE document ALTER file_type SET NOT NULL');
        $this->addSql('ALTER TABLE document RENAME COLUMN file_mimetype TO mimetype');
        $this->addSql('ALTER TABLE document RENAME COLUMN file_path TO filepath');
        $this->addSql('ALTER TABLE document RENAME COLUMN file_size TO filesize');
        $this->addSql('ALTER TABLE document RENAME COLUMN file_uploaded TO uploaded');
    }
}
