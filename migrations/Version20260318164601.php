<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260318164601 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier ALTER publication_date TYPE DATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier ALTER publication_date TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
    }
}
