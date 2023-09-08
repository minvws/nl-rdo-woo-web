<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230829083116 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix decision_document-dossier relation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier DROP CONSTRAINT fk_3d48e0372ecde55e');
        $this->addSql('DROP INDEX uniq_3d48e0372ecde55e');
        $this->addSql('ALTER TABLE dossier DROP decision_document_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE dossier ADD decision_document_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN dossier.decision_document_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT fk_3d48e0372ecde55e FOREIGN KEY (decision_document_id) REFERENCES decision_document (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_3d48e0372ecde55e ON dossier (decision_document_id)');
    }
}
