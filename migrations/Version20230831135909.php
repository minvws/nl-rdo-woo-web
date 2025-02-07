<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230831135909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add withdraw properties for document';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document ADD withdraw_reason VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD withdraw_explanation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD withdraw_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE document ALTER suspended DROP DEFAULT');
        $this->addSql('ALTER TABLE document ALTER withdrawn DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN document.withdraw_date IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE document DROP withdraw_reason');
        $this->addSql('ALTER TABLE document DROP withdraw_explanation');
        $this->addSql('ALTER TABLE document DROP withdraw_date');
        $this->addSql('ALTER TABLE document ALTER suspended SET DEFAULT false');
        $this->addSql('ALTER TABLE document ALTER withdrawn SET DEFAULT false');
    }
}
