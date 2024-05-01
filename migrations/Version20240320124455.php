<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240320124455 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier ADD previous_version_link VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dossier ADD parties JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier DROP previous_version_link');
        $this->addSql('ALTER TABLE dossier DROP parties');
    }
}
