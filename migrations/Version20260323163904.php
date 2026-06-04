<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323163904 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document ALTER document_date TYPE DATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document ALTER document_date TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
    }
}
