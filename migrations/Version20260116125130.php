<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260116125130 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE attachment ADD external_id VARCHAR(128) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_795FD9BB611C0C569F75D7B0 ON attachment (dossier_id, external_id)');
        $this->addSql('ALTER INDEX document_unique_external_id RENAME TO UNIQ_D8698A769F75D7B0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_795FD9BB611C0C569F75D7B0');
        $this->addSql('ALTER TABLE attachment DROP external_id');
        $this->addSql('ALTER INDEX uniq_d8698a769f75d7b0 RENAME TO document_unique_external_id');
    }
}
