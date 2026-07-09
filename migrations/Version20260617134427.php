<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617134427 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX dossier_unique_index');
        $this->addSql('ALTER TABLE dossier RENAME COLUMN dossier_nr TO dossier_number');
        $this->addSql('CREATE UNIQUE INDEX dossier_unique_index ON dossier (dossier_number, document_prefix)');

        $this->addSql('DROP INDEX idx_d8698a7678aa5ba1');
        $this->addSql('ALTER TABLE document RENAME COLUMN document_nr TO document_number');
        $this->addSql('CREATE INDEX IDX_D8698A7628F2AE32 ON document (document_number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX dossier_unique_index');
        $this->addSql('ALTER TABLE dossier RENAME COLUMN dossier_number TO dossier_nr');
        $this->addSql('CREATE UNIQUE INDEX dossier_unique_index ON dossier (dossier_nr, document_prefix)');

        $this->addSql('DROP INDEX IDX_D8698A7628F2AE32');
        $this->addSql('ALTER TABLE document RENAME COLUMN document_number TO document_nr');
        $this->addSql('CREATE INDEX idx_d8698a7678aa5ba1 ON document (document_nr)');
    }
}
