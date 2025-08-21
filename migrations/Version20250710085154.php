<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250710085154 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier ALTER dossier_nr TYPE VARCHAR(50)');
        $this->addSql('ALTER TABLE dossier ALTER previous_version_link TYPE VARCHAR(2048)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier ALTER dossier_nr TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE dossier ALTER previous_version_link TYPE VARCHAR(255)');
    }
}
