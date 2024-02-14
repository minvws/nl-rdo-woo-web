<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240102145100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_3d48e037da892fc0');
        $this->addSql('CREATE UNIQUE INDEX dossier_unique_index ON dossier (dossier_nr, document_prefix)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX dossier_unique_index');
        $this->addSql('CREATE UNIQUE INDEX uniq_3d48e037da892fc0 ON dossier (dossier_nr)');
    }
}
