<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240808083829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add subject table and relations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE subject (id UUID NOT NULL, organisation_id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FBCE3E7A9E6B1585 ON subject (organisation_id)');
        $this->addSql('COMMENT ON COLUMN subject.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN subject.organisation_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE subject ADD CONSTRAINT FK_FBCE3E7A9E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dossier ADD subject_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN dossier.subject_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E03723EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_3D48E03723EDC87 ON dossier (subject_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier DROP CONSTRAINT FK_3D48E03723EDC87');
        $this->addSql('ALTER TABLE subject DROP CONSTRAINT FK_FBCE3E7A9E6B1585');
        $this->addSql('DROP TABLE subject');
        $this->addSql('DROP INDEX IDX_3D48E03723EDC87');
        $this->addSql('ALTER TABLE dossier DROP subject_id');
    }
}
