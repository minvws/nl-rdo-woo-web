<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231023090147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE login_activity (id UUID NOT NULL, account_id UUID NOT NULL, login_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_82D029C59B6B5FBA ON login_activity (account_id)');
        $this->addSql('COMMENT ON COLUMN login_activity.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN login_activity.account_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN login_activity.login_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE login_activity ADD CONSTRAINT FK_82D029C59B6B5FBA FOREIGN KEY (account_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE login_activity DROP CONSTRAINT FK_82D029C59B6B5FBA');
        $this->addSql('DROP TABLE login_activity');
    }
}
