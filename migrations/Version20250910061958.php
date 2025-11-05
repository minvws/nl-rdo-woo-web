<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250910061958 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier ADD advisory_bodies JSON DEFAULT NULL');
        $this->addSql("UPDATE dossier SET advisory_bodies='[]' WHERE type='request-for-advice'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier DROP advisory_bodies');
    }
}
