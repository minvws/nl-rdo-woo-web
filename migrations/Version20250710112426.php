<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250710112426 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE department ALTER name TYPE VARCHAR(100)');
        $this->addSql('ALTER TABLE department ALTER slug TYPE VARCHAR(100)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE department ALTER name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE department ALTER slug TYPE VARCHAR(20)');
    }
}
