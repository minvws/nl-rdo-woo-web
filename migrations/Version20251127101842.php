<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251127101842 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier ADD external_id VARCHAR(128) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_3D48E0379F75D7B0 ON dossier (external_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_3D48E0379F75D7B0');
        $this->addSql('ALTER TABLE dossier DROP external_id');
    }
}
