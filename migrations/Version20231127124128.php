<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231127124128 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier ADD organisation_id UUID');
        $this->addSql('COMMENT ON COLUMN dossier.organisation_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E0379E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_3D48E0379E6B1585 ON dossier (organisation_id)');
        $this->addSql('ALTER TABLE inquiry ADD organisation_id UUID');
        $this->addSql('COMMENT ON COLUMN inquiry.organisation_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE inquiry ADD CONSTRAINT FK_5A3903F09E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_5A3903F09E6B1585 ON inquiry (organisation_id)');

        $this->addSql('UPDATE dossier SET organisation_id = (select organisation_id FROM document_prefix WHERE prefix = dossier.document_prefix)');
        $this->addSql('UPDATE inquiry SET organisation_id = (select id from organisation limit 1)');
        $this->addSql('ALTER TABLE dossier ALTER organisation_id SET NOT NULL');
        $this->addSql('ALTER TABLE inquiry ALTER organisation_id SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier DROP CONSTRAINT FK_3D48E0379E6B1585');
        $this->addSql('DROP INDEX IDX_3D48E0379E6B1585');
        $this->addSql('ALTER TABLE dossier DROP organisation_id');
        $this->addSql('ALTER TABLE inquiry DROP CONSTRAINT FK_5A3903F09E6B1585');
        $this->addSql('DROP INDEX IDX_5A3903F09E6B1585');
        $this->addSql('ALTER TABLE inquiry DROP organisation_id');
    }
}
