<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231128105341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE history (id UUID NOT NULL, type VARCHAR(255) NOT NULL, identifier UUID NOT NULL, created_dt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, context_key VARCHAR(255) NOT NULL, context JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_27BA704B8CDE5729772E836A ON history (type, identifier)');
        $this->addSql('COMMENT ON COLUMN history.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN history.identifier IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN history.created_dt IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE history');
    }
}
