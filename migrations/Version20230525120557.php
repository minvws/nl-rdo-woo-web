<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230525120557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dossier_document (dossier_id UUID NOT NULL, document_id UUID NOT NULL, PRIMARY KEY(dossier_id, document_id))');
        $this->addSql('CREATE INDEX IDX_F0296801611C0C56 ON dossier_document (dossier_id)');
        $this->addSql('CREATE INDEX IDX_F0296801C33F7837 ON dossier_document (document_id)');
        $this->addSql('COMMENT ON COLUMN dossier_document.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN dossier_document.document_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE dossier_document ADD CONSTRAINT FK_F0296801611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dossier_document ADD CONSTRAINT FK_F0296801C33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document ADD slurper_progress_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE document ALTER mimetype DROP NOT NULL');
        $this->addSql('ALTER TABLE document ALTER filepath DROP NOT NULL');
        $this->addSql('COMMENT ON COLUMN document.slurper_progress_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A768047EA50 FOREIGN KEY (slurper_progress_id) REFERENCES slurper_progress (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D8698A768047EA50 ON document (slurper_progress_id)');
        $this->addSql('ALTER TABLE dossier ALTER dossier_nr SET DEFAULT \'\'');
        $this->addSql('UPDATE dossier SET dossier_nr = \'\' WHERE dossier_nr IS NULL');
        $this->addSql('ALTER TABLE dossier ALTER dossier_nr SET NOT NULL');
        $this->addSql('ALTER TABLE dossier ALTER summary DROP DEFAULT');
        $this->addSql('ALTER TABLE dossier ALTER document_prefix DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE dossier_document DROP CONSTRAINT FK_F0296801611C0C56');
        $this->addSql('ALTER TABLE dossier_document DROP CONSTRAINT FK_F0296801C33F7837');
        $this->addSql('DROP TABLE dossier_document');
        $this->addSql('ALTER TABLE document DROP CONSTRAINT FK_D8698A768047EA50');
        $this->addSql('DROP INDEX UNIQ_D8698A768047EA50');
        $this->addSql('ALTER TABLE document DROP slurper_progress_id');
        $this->addSql('ALTER TABLE document ALTER mimetype SET NOT NULL');
        $this->addSql('ALTER TABLE document ALTER filepath SET NOT NULL');
        $this->addSql('ALTER TABLE dossier ALTER dossier_nr DROP NOT NULL');
        $this->addSql('ALTER TABLE dossier ALTER summary SET DEFAULT \'\'');
        $this->addSql('ALTER TABLE dossier ALTER document_prefix SET DEFAULT \'\'');
    }
}
