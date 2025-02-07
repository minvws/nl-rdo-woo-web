<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231108135804 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE batch_download ADD inquiry_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE batch_download ALTER dossier_id DROP NOT NULL');
        $this->addSql('COMMENT ON COLUMN batch_download.inquiry_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE batch_download ADD CONSTRAINT FK_F3F4EC10A7AD6D71 FOREIGN KEY (inquiry_id) REFERENCES inquiry (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_F3F4EC10A7AD6D71 ON batch_download (inquiry_id)');
        $this->addSql('ALTER TABLE batch_download ADD filename VARCHAR(255)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE batch_download DROP CONSTRAINT FK_F3F4EC10A7AD6D71');
        $this->addSql('DROP INDEX IDX_F3F4EC10A7AD6D71');
        $this->addSql('ALTER TABLE batch_download DROP inquiry_id');
        $this->addSql('ALTER TABLE batch_download ALTER dossier_id SET NOT NULL');
        $this->addSql('ALTER TABLE batch_download DROP filename');
    }
}
