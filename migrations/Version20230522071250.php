<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230522071250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dossier_department (dossier_id UUID NOT NULL, department_id UUID NOT NULL, PRIMARY KEY(dossier_id, department_id))');
        $this->addSql('CREATE INDEX IDX_E367A5AF611C0C56 ON dossier_department (dossier_id)');
        $this->addSql('CREATE INDEX IDX_E367A5AFAE80F5DF ON dossier_department (department_id)');
        $this->addSql('COMMENT ON COLUMN dossier_department.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN dossier_department.department_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE dossier_department_head (dossier_id UUID NOT NULL, department_head_id UUID NOT NULL, PRIMARY KEY(dossier_id, department_head_id))');
        $this->addSql('CREATE INDEX IDX_4ADF1A7D611C0C56 ON dossier_department_head (dossier_id)');
        $this->addSql('CREATE INDEX IDX_4ADF1A7D49B3897D ON dossier_department_head (department_head_id)');
        $this->addSql('COMMENT ON COLUMN dossier_department_head.dossier_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN dossier_department_head.department_head_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE dossier_department ADD CONSTRAINT FK_E367A5AF611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dossier_department ADD CONSTRAINT FK_E367A5AFAE80F5DF FOREIGN KEY (department_id) REFERENCES department (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dossier_department_head ADD CONSTRAINT FK_4ADF1A7D611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE dossier_department_head ADD CONSTRAINT FK_4ADF1A7D49B3897D FOREIGN KEY (department_head_id) REFERENCES department_head (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_department DROP CONSTRAINT FK_E367A5AF611C0C56');
        $this->addSql('ALTER TABLE dossier_department DROP CONSTRAINT FK_E367A5AFAE80F5DF');
        $this->addSql('ALTER TABLE dossier_department_head DROP CONSTRAINT FK_4ADF1A7D611C0C56');
        $this->addSql('ALTER TABLE dossier_department_head DROP CONSTRAINT FK_4ADF1A7D49B3897D');
        $this->addSql('DROP TABLE dossier_department');
        $this->addSql('DROP TABLE dossier_department_head');
    }
}
