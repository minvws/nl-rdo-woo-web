<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230525083336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_government_official DROP CONSTRAINT fk_4adf1a7d49b3897d');
        $this->addSql('DROP INDEX idx_4adf1a7d49b3897d');
        $this->addSql('ALTER TABLE dossier_government_official RENAME COLUMN department_head_id TO government_official_id');
        $this->addSql('ALTER TABLE dossier_government_official ADD CONSTRAINT FK_C79596A154DE3212 FOREIGN KEY (government_official_id) REFERENCES government_official (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C79596A154DE3212 ON dossier_government_official (government_official_id)');
        $this->addSql('ALTER INDEX idx_4adf1a7d611c0c56 RENAME TO IDX_C79596A1611C0C56');
        $this->addSql('ALTER INDEX idx_5596e8b667ee6561 RENAME TO IDX_7E3EC07FA7AD6D71');
        $this->addSql('ALTER INDEX idx_5596e8b6c33f7837 RENAME TO IDX_7E3EC07FC33F7837');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE dossier_government_official DROP CONSTRAINT FK_C79596A154DE3212');
        $this->addSql('DROP INDEX IDX_C79596A154DE3212');
        $this->addSql('DROP INDEX dossier_department_head_pkey');
        $this->addSql('ALTER TABLE dossier_government_official RENAME COLUMN government_official_id TO department_head_id');
        $this->addSql('ALTER TABLE dossier_government_official ADD CONSTRAINT fk_4adf1a7d49b3897d FOREIGN KEY (department_head_id) REFERENCES government_official (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_4adf1a7d49b3897d ON dossier_government_official (department_head_id)');
        $this->addSql('ALTER INDEX idx_c79596a1611c0c56 RENAME TO idx_4adf1a7d611c0c56');
        $this->addSql('ALTER INDEX idx_7e3ec07fc33f7837 RENAME TO idx_5596e8b6c33f7837');
        $this->addSql('ALTER INDEX idx_7e3ec07fa7ad6d71 RENAME TO idx_5596e8b667ee6561');
    }
}
