<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231107090907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE inquiry_inventory (id UUID NOT NULL, inquiry_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, file_mimetype VARCHAR(100) DEFAULT NULL, file_path VARCHAR(1024) DEFAULT NULL, file_size INT NOT NULL, file_type VARCHAR(255) DEFAULT NULL, file_name VARCHAR(1024) DEFAULT NULL, file_uploaded BOOLEAN NOT NULL, file_source_type VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C857A5D8A7AD6D71 ON inquiry_inventory (inquiry_id)');
        $this->addSql('COMMENT ON COLUMN inquiry_inventory.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN inquiry_inventory.inquiry_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN inquiry_inventory.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN inquiry_inventory.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE inquiry_inventory ADD CONSTRAINT FK_C857A5D8A7AD6D71 FOREIGN KEY (inquiry_id) REFERENCES inquiry (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inquiry_inventory DROP CONSTRAINT FK_C857A5D8A7AD6D71');
        $this->addSql('DROP TABLE inquiry_inventory');
    }
}
