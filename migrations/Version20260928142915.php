<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260928142915 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make subject landing_page_status and landing_page_content_tree_status NOT NULL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE subject SET landing_page_status = 'concept' WHERE landing_page_status IS NULL");
        $this->addSql('ALTER TABLE subject ALTER landing_page_status SET NOT NULL');
        $this->addSql('ALTER TABLE subject ALTER landing_page_content_tree_status SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subject ALTER landing_page_status DROP NOT NULL');
        $this->addSql('ALTER TABLE subject ALTER landing_page_content_tree_status DROP NOT NULL');
    }
}
