<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230526085611 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_document DROP CONSTRAINT fk_f0296801611c0c56');
        $this->addSql('ALTER TABLE dossier_document DROP CONSTRAINT fk_f0296801c33f7837');
        $this->addSql('DROP TABLE dossier_document');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dossier_document (dossier_id UUID NOT NULL, document_id UUID NOT NULL, PRIMARY KEY(dossier_id, document_id))');
        $this->addSql('CREATE INDEX idx_f0296801c33f7837 ON dossier_document (document_id)');
        $this->addSql('CREATE INDEX idx_f0296801611c0c56 ON dossier_document (dossier_id)');
        $this->addSql('COMMENT ON COLUMN dossier_document.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN dossier_document.document_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE dossier_document ADD CONSTRAINT fk_f0296801611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dossier_document ADD CONSTRAINT fk_f0296801c33f7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
