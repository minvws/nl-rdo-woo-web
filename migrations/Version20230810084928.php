<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230810084928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert empty string judgement values to NULL to prevent mapping errors';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE document SET judgement=NULL WHERE judgement=''");
    }

    public function down(Schema $schema): void
    {
    }
}
