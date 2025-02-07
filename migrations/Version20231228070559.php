<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231228070559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove token table, no longer used';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE token DROP CONSTRAINT fk_5f37a13b611c0c56');
        $this->addSql('DROP TABLE token');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE token (id UUID NOT NULL, dossier_id UUID DEFAULT NULL, expiry_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, remark VARCHAR(1024) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_5f37a13b611c0c56 ON token (dossier_id)');
        $this->addSql('COMMENT ON COLUMN token.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN token.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN token.expiry_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE token ADD CONSTRAINT fk_5f37a13b611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
