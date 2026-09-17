<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822055205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill organisation prefixes from the first active document prefix';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE organisation AS o
            SET prefix = (
                SELECT dp.prefix
                FROM document_prefix AS dp
                WHERE dp.organisation_id = o.id
                  AND dp.archived = false
                ORDER BY dp.prefix ASC
                LIMIT 1
            )
            WHERE o.prefix IS NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE organisation SET prefix = NULL');
    }
}
