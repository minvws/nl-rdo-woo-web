<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260924120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace subject.has_visible_landing_page_content_tree (boolean) with subject.landing_page_content_tree_status (enum string)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE subject ADD landing_page_content_tree_status VARCHAR(255) DEFAULT NULL");
        $this->addSql("UPDATE subject SET landing_page_content_tree_status = CASE WHEN has_visible_landing_page_content_tree THEN 'published' ELSE 'concept' END");
        $this->addSql('ALTER TABLE subject DROP has_visible_landing_page_content_tree');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subject ADD has_visible_landing_page_content_tree BOOLEAN DEFAULT false NOT NULL');
        $this->addSql("UPDATE subject SET has_visible_landing_page_content_tree = (landing_page_content_tree_status = 'published')");
        $this->addSql('ALTER TABLE subject DROP landing_page_content_tree_status');
    }
}
