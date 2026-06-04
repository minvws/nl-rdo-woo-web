<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260318144920 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organisation_department DROP CONSTRAINT fk_45f0f7b89e6b1585');
        $this->addSql('ALTER TABLE organisation_department ADD CONSTRAINT FK_45F0F7B89E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE upload ALTER external_id TYPE VARCHAR(128)');
        $this->addSql('CREATE INDEX IDX_17BDE61F9F75D7B0 ON upload (external_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organisation_department DROP CONSTRAINT FK_45F0F7B89E6B1585');
        $this->addSql('ALTER TABLE organisation_department ADD CONSTRAINT fk_45f0f7b89e6b1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP INDEX IDX_17BDE61F9F75D7B0');
        $this->addSql('ALTER TABLE upload ALTER external_id TYPE VARCHAR(255)');
    }
}
