<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230815072107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added link and remark as nullable entries to the document table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document ADD link VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD remark TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document DROP link');
        $this->addSql('ALTER TABLE document DROP remark');
    }
}
