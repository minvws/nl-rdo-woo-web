<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230523084457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE woo_request_document (woo_request_id UUID NOT NULL, document_id UUID NOT NULL, PRIMARY KEY(woo_request_id, document_id))');
        $this->addSql('CREATE INDEX IDX_5596E8B667EE6561 ON woo_request_document (woo_request_id)');
        $this->addSql('CREATE INDEX IDX_5596E8B6C33F7837 ON woo_request_document (document_id)');
        $this->addSql('COMMENT ON COLUMN woo_request_document.woo_request_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN woo_request_document.document_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE woo_request_document ADD CONSTRAINT FK_5596E8B667EE6561 FOREIGN KEY (woo_request_id) REFERENCES woo_request (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE woo_request_document ADD CONSTRAINT FK_5596E8B6C33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE token ALTER dossier_id DROP NOT NULL');
        $this->addSql('ALTER TABLE woo_request DROP CONSTRAINT fk_3555569f611c0c56');
        $this->addSql('DROP INDEX idx_3555569f611c0c56');
        $this->addSql('ALTER TABLE woo_request DROP dossier_id');
        $this->addSql('ALTER TABLE woo_request DROP applicant');
        $this->addSql('ALTER TABLE woo_request DROP description');
        $this->addSql('ALTER TABLE woo_request DROP status');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE woo_request_document DROP CONSTRAINT FK_5596E8B667EE6561');
        $this->addSql('ALTER TABLE woo_request_document DROP CONSTRAINT FK_5596E8B6C33F7837');
        $this->addSql('DROP TABLE woo_request_document');
        $this->addSql('ALTER TABLE woo_request ADD dossier_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE woo_request ADD applicant VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE woo_request ADD description VARCHAR(1024) NOT NULL');
        $this->addSql('ALTER TABLE woo_request ADD status VARCHAR(255) NOT NULL');
        $this->addSql('COMMENT ON COLUMN woo_request.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE woo_request ADD CONSTRAINT fk_3555569f611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_3555569f611c0c56 ON woo_request (dossier_id)');
        $this->addSql('ALTER TABLE token ALTER dossier_id SET NOT NULL');
    }
}
