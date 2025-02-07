<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230525120927 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE document_dossier (document_id UUID NOT NULL, dossier_id UUID NOT NULL, PRIMARY KEY(document_id, dossier_id))');
        $this->addSql('CREATE INDEX IDX_992746EBC33F7837 ON document_dossier (document_id)');
        $this->addSql('CREATE INDEX IDX_992746EB611C0C56 ON document_dossier (dossier_id)');
        $this->addSql('COMMENT ON COLUMN document_dossier.document_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN document_dossier.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE document_dossier ADD CONSTRAINT FK_992746EBC33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document_dossier ADD CONSTRAINT FK_992746EB611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document DROP CONSTRAINT fk_d8698a76611c0c56');
        $this->addSql('DROP INDEX idx_d8698a76611c0c56');
        $this->addSql('ALTER TABLE document DROP dossier_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE document_dossier DROP CONSTRAINT FK_992746EBC33F7837');
        $this->addSql('ALTER TABLE document_dossier DROP CONSTRAINT FK_992746EB611C0C56');
        $this->addSql('DROP TABLE document_dossier');
        $this->addSql('ALTER TABLE document ADD dossier_id UUID NOT NULL');
        $this->addSql('COMMENT ON COLUMN document.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT fk_d8698a76611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_d8698a76611c0c56 ON document (dossier_id)');
    }
}
