<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230517070927 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE woo_request ADD dossier_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN woo_request.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE woo_request ADD CONSTRAINT FK_3555569F611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_3555569F611C0C56 ON woo_request (dossier_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE woo_request DROP CONSTRAINT FK_3555569F611C0C56');
        $this->addSql('DROP INDEX IDX_3555569F611C0C56');
        $this->addSql('ALTER TABLE woo_request DROP dossier_id');
    }
}
