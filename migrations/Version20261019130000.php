<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add error_reason to upload';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upload ADD error_reason VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upload DROP error_reason');
    }
}
