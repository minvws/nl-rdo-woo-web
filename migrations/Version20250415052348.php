<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250415052348 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX lower_case_document_nr ON document ((lower(document_nr)))
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_D8698A7678AA5BA1
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_D8698A7678AA5BA1 ON document (document_nr)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX lower_case_document_nr
        SQL);
    }
}
