<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241007071848 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate separate (woo-)decision_document into shared main_document table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO main_document(
                            id,
                            dossier_id,
                            formal_date,
                            type,
                            internal_reference,
                            language,
                            grounds,
                            created_at,
                            updated_at,
                            file_mimetype,
                            file_path,
                            file_size,
                            file_type,
                            file_source_type,
                            file_name,
                            file_uploaded,
                            file_page_count,
                            file_paginatable,
                            entity_type,
                            file_hash
                       ) SELECT
                             d.id,
                             d.dossier_id,
                             COALESCE(dos.publication_date, d.created_at),
                             \'c_4f50ca9c\',
                             \'\',
                             \'Dutch\',
                             \'[]\',
                             d.created_at,
                             d.updated_at,
                             d.file_mimetype,
                             d.file_path,
                             d.file_size,
                             d.file_type,
                             d.file_source_type,
                             d.file_name,
                             d.file_uploaded,
                             d.file_page_count,
                             d.file_paginatable,
                             \'woo_decision_main_document\',
                             d.file_hash
                       FROM decision_document d
                       JOIN dossier dos ON dos.id = d.dossier_id');

        $this->addSql('ALTER TABLE decision_document DROP CONSTRAINT fk_55b54548611c0c56');
        $this->addSql('DROP TABLE decision_document');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE decision_document (id UUID NOT NULL, dossier_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_source_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_page_count INT DEFAULT NULL, file_paginatable BOOLEAN DEFAULT false NOT NULL, file_hash VARCHAR(128) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_55b54548611c0c56 ON decision_document (dossier_id)');
        $this->addSql('COMMENT ON COLUMN decision_document.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN decision_document.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN decision_document.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN decision_document.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE decision_document ADD CONSTRAINT fk_55b54548611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
