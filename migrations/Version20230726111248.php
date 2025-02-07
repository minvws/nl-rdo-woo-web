<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230726111248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make prefix column unique in table document_prefix';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2DD337E893B1868E ON document_prefix (prefix)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_2DD337E893B1868E');
    }
}
