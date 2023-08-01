<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230517071219 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier DROP CONSTRAINT fk_3d48e03767ee6561');
        $this->addSql('DROP INDEX idx_3d48e03767ee6561');
        $this->addSql('ALTER TABLE dossier DROP woo_request_id');
        $this->addSql('ALTER TABLE "user" ALTER mfa_token TYPE TEXT');
        $this->addSql('ALTER TABLE "user" ALTER mfa_recovery TYPE TEXT');
        $this->addSql('ALTER TABLE "user" ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER updated_at DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier ADD woo_request_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN dossier.woo_request_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT fk_3d48e03767ee6561 FOREIGN KEY (woo_request_id) REFERENCES woo_request (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_3d48e03767ee6561 ON dossier (woo_request_id)');
        $this->addSql('ALTER TABLE "user" ALTER created_at SET DEFAULT \'CURRENT_TIMESTAMP(0)\'');
        $this->addSql('ALTER TABLE "user" ALTER updated_at SET DEFAULT \'CURRENT_TIMESTAMP(0)\'');
    }
}
