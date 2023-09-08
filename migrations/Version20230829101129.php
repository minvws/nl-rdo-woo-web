<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230829101129 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Related to change in inquiry dossier relation mapping';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inquiry_dossier DROP CONSTRAINT inquiry_dossier_pkey');
        $this->addSql('ALTER TABLE inquiry_dossier ADD PRIMARY KEY (inquiry_id, dossier_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX inquiry_dossier_pkey');
        $this->addSql('ALTER TABLE inquiry_dossier ADD PRIMARY KEY (dossier_id, inquiry_id)');
    }
}
