<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230707074725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE inquiry_dossier (inquiry_id UUID NOT NULL, dossier_id UUID NOT NULL, PRIMARY KEY(inquiry_id, dossier_id))');
        $this->addSql('CREATE INDEX IDX_D6558E92A7AD6D71 ON inquiry_dossier (inquiry_id)');
        $this->addSql('CREATE INDEX IDX_D6558E92611C0C56 ON inquiry_dossier (dossier_id)');
        $this->addSql('COMMENT ON COLUMN inquiry_dossier.inquiry_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN inquiry_dossier.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE inquiry_dossier ADD CONSTRAINT FK_D6558E92A7AD6D71 FOREIGN KEY (inquiry_id) REFERENCES inquiry (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE inquiry_dossier ADD CONSTRAINT FK_D6558E92611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE inquiry_dossier DROP CONSTRAINT FK_D6558E92A7AD6D71');
        $this->addSql('ALTER TABLE inquiry_dossier DROP CONSTRAINT FK_D6558E92611C0C56');
        $this->addSql('DROP TABLE inquiry_dossier');
        $this->addSql('COMMENT ON COLUMN inquiry.preview_until IS \'(DC2Type:datetime_immutable)\'');
    }
}
