<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240709134450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE organisation_department (organisation_id UUID NOT NULL, department_id UUID NOT NULL, PRIMARY KEY(organisation_id, department_id))');
        $this->addSql('CREATE INDEX IDX_45F0F7B89E6B1585 ON organisation_department (organisation_id)');
        $this->addSql('CREATE INDEX IDX_45F0F7B8AE80F5DF ON organisation_department (department_id)');
        $this->addSql('COMMENT ON COLUMN organisation_department.organisation_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organisation_department.department_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE organisation_department ADD CONSTRAINT FK_45F0F7B89E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organisation_department ADD CONSTRAINT FK_45F0F7B8AE80F5DF FOREIGN KEY (department_id) REFERENCES department (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organisation DROP CONSTRAINT fk_e6e132b4ae80f5df');
        $this->addSql('DROP INDEX idx_e6e132b4ae80f5df');
        $this->addSql('INSERT INTO organisation_department(organisation_id, department_id) SELECT id, department_id FROM organisation');
        $this->addSql('ALTER TABLE organisation DROP department_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organisation_department DROP CONSTRAINT FK_45F0F7B89E6B1585');
        $this->addSql('ALTER TABLE organisation_department DROP CONSTRAINT FK_45F0F7B8AE80F5DF');
        $this->addSql('DROP TABLE organisation_department');
        $this->addSql('ALTER TABLE organisation ADD department_id UUID NOT NULL');
        $this->addSql('COMMENT ON COLUMN organisation.department_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE organisation ADD CONSTRAINT fk_e6e132b4ae80f5df FOREIGN KEY (department_id) REFERENCES department (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_e6e132b4ae80f5df ON organisation (department_id)');
    }
}
