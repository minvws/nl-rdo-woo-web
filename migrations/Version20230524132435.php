<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230524132435 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ALTER changepwd DROP DEFAULT');
        $this->addSql('ALTER TABLE woo_request ALTER token DROP DEFAULT');

        $this->addSql('ALTER TABLE department_head RENAME TO government_official');
        $this->addSql('ALTER TABLE dossier_department_head RENAME TO dossier_government_official');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE woo_request ALTER token SET DEFAULT \'\'');
        $this->addSql('ALTER TABLE "user" ALTER changepwd SET DEFAULT false');

        $this->addSql('ALTER TABLE government_official RENAME TO department_head');
        $this->addSql('ALTER TABLE dossier_government_official RENAME TO dossier_department_head');
    }
}
