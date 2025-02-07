<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230726072400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add publication_date to dossier';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier ADD publication_date DATE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN dossier.publication_date IS \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier DROP publication_date');
    }
}
