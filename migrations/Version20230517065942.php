<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230517065942 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE woo_request (id UUID NOT NULL, casenr VARCHAR(255) NOT NULL, applicant VARCHAR(255) NOT NULL, description VARCHAR(1024) NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN woo_request.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN woo_request.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN woo_request.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE dossier ADD woo_request_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN dossier.woo_request_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E03767EE6561 FOREIGN KEY (woo_request_id) REFERENCES woo_request (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_3D48E03767EE6561 ON dossier (woo_request_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier DROP CONSTRAINT FK_3D48E03767EE6561');
        $this->addSql('DROP TABLE woo_request');
        $this->addSql('DROP INDEX IDX_3D48E03767EE6561');
        $this->addSql('ALTER TABLE dossier DROP woo_request_id');
    }
}
