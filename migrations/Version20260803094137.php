<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260803094137 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE worker_stats');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE worker_stats (id UUID NOT NULL, section VARCHAR(100) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, duration INT NOT NULL, hostname VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
    }
}
