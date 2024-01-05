<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231214121436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE dossier SET default_subjects = replace(default_subjects::text, '\"Vaccinaties & medicatie\"', '\"Vaccinaties en medicatie\"')::jsonb;");
        $this->addSql("UPDATE document SET subjects = replace(subjects::text, '\"Vaccinaties & medicatie\"', '\"Vaccinaties en medicatie\"')::jsonb;");
    }

    public function down(Schema $schema): void
    {
    }
}
