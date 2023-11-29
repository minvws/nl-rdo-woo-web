<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230928093756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Using department_id instead of department as a free input field';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organisation ADD department_id UUID NOT NULL');
        $this->addSql('ALTER TABLE organisation DROP department');
        $this->addSql('COMMENT ON COLUMN organisation.department_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE organisation ADD CONSTRAINT FK_E6E132B4AE80F5DF FOREIGN KEY (department_id) REFERENCES department (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_E6E132B4AE80F5DF ON organisation (department_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organisation DROP CONSTRAINT FK_E6E132B4AE80F5DF');
        $this->addSql('DROP INDEX IDX_E6E132B4AE80F5DF');
        $this->addSql('ALTER TABLE organisation ADD department VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE organisation DROP department_id');
    }
}
