<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230829082733 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix inventory-dossier relation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier DROP CONSTRAINT fk_3d48e0379eea759');
        $this->addSql('DROP INDEX uniq_3d48e0379eea759');
        $this->addSql('ALTER TABLE dossier DROP inventory_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE dossier ADD inventory_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN dossier.inventory_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT fk_3d48e0379eea759 FOREIGN KEY (inventory_id) REFERENCES inventory (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_3d48e0379eea759 ON dossier (inventory_id)');
    }
}
