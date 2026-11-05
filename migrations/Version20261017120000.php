<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261017120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill subject landing_page_preview_token and make it NOT NULL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE subject SET landing_page_preview_token = gen_random_uuid() WHERE landing_page_preview_token IS NULL');
        $this->addSql('ALTER TABLE subject ALTER landing_page_preview_token SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subject ALTER landing_page_preview_token DROP NOT NULL');
    }
}
