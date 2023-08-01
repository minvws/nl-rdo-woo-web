<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230627074027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE batch_download (id UUID NOT NULL, dossier_id UUID NOT NULL, expiration TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, downloaded INT NOT NULL, documents JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F3F4EC10611C0C56 ON batch_download (dossier_id)');
        $this->addSql('COMMENT ON COLUMN batch_download.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN batch_download.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN batch_download.expiration IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE batch_download ADD CONSTRAINT FK_F3F4EC10611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch_download DROP CONSTRAINT FK_F3F4EC10611C0C56');
        $this->addSql('DROP TABLE batch_download');
    }
}
