<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230929070108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        $this->addSql('alter table department add constraint department_pk unique (name)');
        $this->addSql('INSERT INTO department (id, name, short_tag) VALUES (uuid_generate_v4(), \'Ministerie van Volksgezondheid, Welzijn en Sport\', \'VWS\') on conflict (name) do nothing;');
        $this->addSql('INSERT INTO organisation (id, name, created_at, updated_at, department_id) VALUES (uuid_generate_v4(), \'Programmadirectie Openbaarheid\', now(), now(), (SELECT id as department_id FROM department WHERE name=\'Ministerie van Volksgezondheid, Welzijn en Sport\'))');
        $this->addSql('UPDATE "user" SET organisation_id=(SELECT id as organisation_id FROM organisation WHERE name=\'Programmadirectie Openbaarheid\')');

        $this->addSql('ALTER TABLE "user" ALTER organisation_id SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ALTER organisation_id DROP NOT NULL');
    }
}
