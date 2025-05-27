<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250520091110 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE main_document
            SET formal_date = d.decision_date
            FROM dossier d
            WHERE d.id = main_document.dossier_id
                AND main_document.formal_date != d.decision_date
                AND d.type = 'woo-decision'
        ");
    }

    public function down(Schema $schema): void
    {
    }
}
