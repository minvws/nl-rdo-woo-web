<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821074045 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add organisation prefix column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organisation ADD prefix VARCHAR(30) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_e6e132b493b1868e ON organisation (prefix)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_e6e132b493b1868e');
        $this->addSql('ALTER TABLE organisation DROP prefix');
    }
}
