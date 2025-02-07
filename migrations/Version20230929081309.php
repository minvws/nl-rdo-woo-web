<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230929081309 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE document_prefix SET organisation_id = (SELECT id FROM "organisation" WHERE name LIKE \'Programmadirectie Openbaarheid\');');
        $this->addSql('ALTER TABLE document_prefix ALTER organisation_id SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_prefix ALTER organisation_id DROP NOT NULL');
    }
}
