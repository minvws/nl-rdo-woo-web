<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251201151332 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX dossier_unique_external_id ON dossier (external_id, organisation_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX dossier_unique_external_id');
    }
}
