<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231214125604 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert document links from string to json (array of strings)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document ADD links JSON DEFAULT NULL');
        $this->addSql('UPDATE document SET links = json_build_array(link)');
        $this->addSql('ALTER TABLE document DROP link');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document ADD link VARCHAR(2048) DEFAULT NULL');
        $this->addSql('ALTER TABLE document DROP links');
    }
}
