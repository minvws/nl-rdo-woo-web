<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240207084512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier ADD type VARCHAR(255)');
        $this->addSql('ALTER TABLE dossier ALTER publication_reason DROP NOT NULL');
        $this->addSql("UPDATE dossier SET type='woo-decision'");
        $this->addSql('ALTER TABLE dossier ALTER type SET NOT NULL');
        $this->addSql('ALTER TABLE dossier ADD internal_reference VARCHAR(255)');
        $this->addSql("UPDATE dossier SET internal_reference=''");
        $this->addSql('ALTER TABLE dossier ALTER internal_reference SET NOT NULL');
        $this->addSql('ALTER TABLE dossier ALTER decision DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier DROP internal_reference');
        $this->addSql('ALTER TABLE dossier ALTER decision SET NOT NULL');
        $this->addSql('ALTER TABLE dossier DROP type');
        $this->addSql('ALTER TABLE dossier ALTER publication_reason SET NOT NULL');
    }
}
