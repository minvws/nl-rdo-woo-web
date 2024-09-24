<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240731051048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE department ADD slug VARCHAR(20)');
        $this->addSql('ALTER TABLE department ADD public BOOLEAN NOT NULL DEFAULT TRUE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CD1DE18A74C9F71C ON department (short_tag)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CD1DE18A989D9B62 ON department (slug)');
        $this->addSql("UPDATE department SET slug = lower(regexp_replace(short_tag, '[^\w]+',''))");
        $this->addSql('ALTER TABLE department ALTER slug SET NOT NULL');
        $this->addSql('ALTER INDEX department_pk RENAME TO UNIQ_CD1DE18A5E237E06');
        $this->addSql('ALTER TABLE department ALTER short_tag SET NOT NULL');
        $this->addSql('ALTER TABLE department ALTER public DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE department ALTER short_tag DROP NOT NULL');
        $this->addSql('ALTER INDEX UNIQ_CD1DE18A5E237E06 RENAME TO department_pk');
        $this->addSql('DROP INDEX UNIQ_CD1DE18A74C9F71C');
        $this->addSql('DROP INDEX UNIQ_CD1DE18A989D9B62');
        $this->addSql('ALTER TABLE department DROP slug');
        $this->addSql('ALTER TABLE department DROP public');
    }
}
