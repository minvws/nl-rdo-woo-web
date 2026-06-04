<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260318162428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate attachment and main_document language values from Dutch/English to TOOI codes (NLD/ENG)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE main_document SET language = CASE language
            WHEN 'Dutch'   THEN 'NLD'
            WHEN 'English' THEN 'ENG'
            ELSE 'NLD'
        END");

        $this->addSql("UPDATE attachment SET language = CASE language
            WHEN 'Dutch'   THEN 'NLD'
            WHEN 'English' THEN 'ENG'
            ELSE 'NLD'
        END");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE main_document SET language = CASE language
            WHEN 'NLD' THEN 'Dutch'
            WHEN 'ENG' THEN 'English'
            ELSE 'Dutch'
        END");

        $this->addSql("UPDATE attachment SET language = CASE language
            WHEN 'NLD' THEN 'Dutch'
            WHEN 'ENG' THEN 'English'
            ELSE 'Dutch'
        END");
    }
}
