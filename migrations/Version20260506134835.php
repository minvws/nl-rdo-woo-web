<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506134835 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notice_not_public (id UUID NOT NULL, document_name VARCHAR(255) DEFAULT NULL, formal_date DATE NOT NULL, grounds JSON NOT NULL, explanation TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, dossier_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_31A50C68611C0C56 ON notice_not_public (dossier_id)');
        $this->addSql('ALTER TABLE notice_not_public ADD CONSTRAINT FK_31A50C68611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notice_not_public DROP CONSTRAINT FK_31A50C68611C0C56');
        $this->addSql('DROP TABLE notice_not_public');
    }
}
