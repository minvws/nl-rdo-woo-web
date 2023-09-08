<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230814092616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert enum values for judgement to english';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE document SET judgement=NULL WHERE judgement=''");
        $this->addSql("UPDATE document SET judgement='public' WHERE LOWER(judgement) = 'openbaar'");
        $this->addSql("UPDATE document SET judgement='partial_public' WHERE LOWER(judgement) = 'deels openbaar'");
        $this->addSql("UPDATE document SET judgement='already_public' WHERE LOWER(judgement) = 'reeds openbaar'");
        $this->addSql("UPDATE document SET judgement='not_public' WHERE LOWER(judgement) = 'niet openbaar'");
    }

    public function down(Schema $schema): void
    {
    }
}
