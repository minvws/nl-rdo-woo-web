<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230522131824 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document DROP department');
        $this->addSql('ALTER TABLE document DROP official');
        $this->addSql('ALTER TABLE document DROP subject');
        $this->addSql('ALTER TABLE document ALTER family_id DROP NOT NULL');
        $this->addSql('ALTER TABLE document ALTER document_id DROP NOT NULL');
        $this->addSql('ALTER TABLE document ALTER thread_id DROP NOT NULL');
        $this->addSql('ALTER TABLE document ALTER judgement DROP NOT NULL');
        $this->addSql('ALTER TABLE document ALTER grounds DROP DEFAULT');
        $this->addSql('ALTER TABLE document ALTER subjects DROP DEFAULT');
        $this->addSql('ALTER TABLE document ALTER grounds TYPE JSON USING grounds::JSON');
        $this->addSql('ALTER TABLE document ALTER subjects TYPE JSON USING subjects::JSON');
        $this->addSql('ALTER TABLE document ALTER period DROP NOT NULL');
        $this->addSql('COMMENT ON COLUMN document.grounds IS NULL');
        $this->addSql('COMMENT ON COLUMN document.subjects IS NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document ADD department JSON NOT NULL');
        $this->addSql('ALTER TABLE document ADD official JSON NOT NULL');
        $this->addSql('ALTER TABLE document ADD subject JSON NOT NULL');
        $this->addSql('ALTER TABLE document ALTER family_id SET NOT NULL');
        $this->addSql('ALTER TABLE document ALTER document_id SET NOT NULL');
        $this->addSql('ALTER TABLE document ALTER thread_id SET NOT NULL');
        $this->addSql('ALTER TABLE document ALTER judgement SET NOT NULL');
        $this->addSql('ALTER TABLE document ALTER grounds TYPE TEXT');
        $this->addSql('ALTER TABLE document ALTER subjects TYPE TEXT');
        $this->addSql('ALTER TABLE document ALTER period SET NOT NULL');
        $this->addSql('COMMENT ON COLUMN document.grounds IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN document.subjects IS \'(DC2Type:array)\'');
    }
}
