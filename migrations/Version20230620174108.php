<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230620174108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE slurper_progress DROP CONSTRAINT fk_b407f9fdc33f7837');
        $this->addSql('DROP TABLE slurper_progress');
        $this->addSql('DROP INDEX uniq_d8698a768047ea50');
        $this->addSql('ALTER TABLE document DROP slurper_progress_id');
        $this->addSql('ALTER TABLE document ALTER family_id DROP DEFAULT');
        $this->addSql('ALTER TABLE document ALTER document_id DROP DEFAULT');
        $this->addSql('ALTER TABLE document ALTER thread_id DROP DEFAULT');
        $this->addSql('ALTER TABLE document ALTER judgement DROP DEFAULT');
        $this->addSql('ALTER TABLE document ALTER period DROP DEFAULT');
        $this->addSql('ALTER TABLE document ALTER uploaded DROP DEFAULT');
        $this->addSql('ALTER TABLE document ALTER source_type DROP DEFAULT');
        $this->addSql('ALTER TABLE document ALTER class DROP DEFAULT');
        $this->addSql('ALTER TABLE document_prefix ALTER description DROP DEFAULT');
        $this->addSql('ALTER TABLE dossier ALTER publication_reason DROP DEFAULT');
        $this->addSql('ALTER TABLE dossier ALTER decision DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE slurper_progress (id UUID NOT NULL, document_id UUID DEFAULT NULL, content TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, pages_processed INT NOT NULL, page_count INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_b407f9fdc33f7837 ON slurper_progress (document_id)');
        $this->addSql('COMMENT ON COLUMN slurper_progress.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN slurper_progress.document_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN slurper_progress.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN slurper_progress.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE slurper_progress ADD CONSTRAINT fk_b407f9fdc33f7837 FOREIGN KEY (document_id) REFERENCES document (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document ADD slurper_progress_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE document ALTER source_type SET DEFAULT \'pdf\'');
        $this->addSql('ALTER TABLE document ALTER family_id SET DEFAULT 0');
        $this->addSql('ALTER TABLE document ALTER document_id SET DEFAULT 0');
        $this->addSql('ALTER TABLE document ALTER thread_id SET DEFAULT 0');
        $this->addSql('ALTER TABLE document ALTER judgement SET DEFAULT \'\'');
        $this->addSql('ALTER TABLE document ALTER period SET DEFAULT \'\'');
        $this->addSql('ALTER TABLE document ALTER uploaded SET DEFAULT false');
        $this->addSql('ALTER TABLE document ALTER class SET DEFAULT \'App\\\\Entity\\\\Document\'');
        $this->addSql('COMMENT ON COLUMN document.slurper_progress_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX uniq_d8698a768047ea50 ON document (slurper_progress_id)');
        $this->addSql('ALTER TABLE dossier ALTER publication_reason SET DEFAULT \'\'');
        $this->addSql('ALTER TABLE dossier ALTER decision SET DEFAULT \'partial_public\'');
        $this->addSql('ALTER TABLE document_prefix ALTER description SET DEFAULT \'\'');
    }
}
