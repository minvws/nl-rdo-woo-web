<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231122093513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE dossier SET default_subjects = replace(default_subjects::text, '\"Opstarten\"', '\"Opstart Corona\"')::jsonb;");
        $this->addSql("UPDATE dossier SET default_subjects = replace(default_subjects::text, '\"Overleggen VWS\"', '\"Overleg VWS\"')::jsonb;");
        $this->addSql("UPDATE dossier SET default_subjects = replace(default_subjects::text, '\"Vaccinaties\"', '\"Vaccinaties & medicatie\"')::jsonb;");

        $this->addSql("UPDATE document SET subjects = replace(subjects::text, '\"Opstarten\"', '\"Opstart Corona\"')::jsonb;");
        $this->addSql("UPDATE document SET subjects = replace(subjects::text, '\"Overleggen VWS\"', '\"Overleg VWS\"')::jsonb;");
        $this->addSql("UPDATE document SET subjects = replace(subjects::text, '\"Vaccinaties\"', '\"Vaccinaties & medicatie\"')::jsonb;");
    }

    public function down(Schema $schema): void
    {
    }
}
