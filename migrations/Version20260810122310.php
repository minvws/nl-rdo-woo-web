<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260810122310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subject ADD landing_page_slug VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE subject ALTER landing_page_title TYPE VARCHAR(200)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FBCE3E7A2A22D98 ON subject (landing_page_slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_FBCE3E7A2A22D98');
        $this->addSql('ALTER TABLE subject DROP landing_page_slug');
        $this->addSql('ALTER TABLE subject ALTER landing_page_title TYPE VARCHAR(100)');
    }
}
