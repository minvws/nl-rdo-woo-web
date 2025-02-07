<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230511132918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE document ALTER department DROP DEFAULT');
        $this->addSql('ALTER TABLE document ALTER official DROP DEFAULT');
        $this->addSql('ALTER TABLE document ALTER document_type DROP DEFAULT');
        $this->addSql('ALTER TABLE document ALTER subject DROP DEFAULT');
        $this->addSql('ALTER TABLE document ALTER publication_type DROP DEFAULT');
        $this->addSql('ALTER TABLE document ALTER publication_section DROP DEFAULT');
        $this->addSql('ALTER TABLE document ALTER document_number DROP DEFAULT');
        $this->addSql('ALTER TABLE document ALTER document_date DROP DEFAULT');
        $this->addSql('ALTER TABLE document ALTER filename DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('ALTER TABLE document ALTER department SET DEFAULT \'[]\'');
        $this->addSql('ALTER TABLE document ALTER official SET DEFAULT \'[]\'');
        $this->addSql('ALTER TABLE document ALTER document_type SET DEFAULT \'\'');
        $this->addSql('ALTER TABLE document ALTER subject SET DEFAULT \'[]\'');
        $this->addSql('ALTER TABLE document ALTER publication_type SET DEFAULT \'\'');
        $this->addSql('ALTER TABLE document ALTER publication_section SET DEFAULT \'\'');
        $this->addSql('ALTER TABLE document ALTER document_number SET DEFAULT \'\'');
        $this->addSql('ALTER TABLE document ALTER document_date SET DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE document ALTER filename SET DEFAULT \'\'');
    }
}
