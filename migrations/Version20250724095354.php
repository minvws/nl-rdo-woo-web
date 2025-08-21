<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250724095354 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE department ADD responsibility_content VARCHAR(1000) DEFAULT NULL');
        $this->addSql('ALTER TABLE department ALTER landing_page_description TYPE VARCHAR(10000)');
        $this->addSql('ALTER TABLE department ALTER feedback_content TYPE VARCHAR(10000)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE department DROP responsibility_content');
        $this->addSql('ALTER TABLE department ALTER landing_page_description TYPE TEXT');
        $this->addSql('ALTER TABLE department ALTER feedback_content TYPE TEXT');
    }
}
