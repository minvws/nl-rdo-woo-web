<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240910111349 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE login_activity DROP CONSTRAINT FK_82D029C59B6B5FBA');
        $this->addSql('ALTER TABLE login_activity ADD CONSTRAINT FK_82D029C59B6B5FBA FOREIGN KEY (account_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE login_activity DROP CONSTRAINT fk_82d029c59b6b5fba');
        $this->addSql('ALTER TABLE login_activity ADD CONSTRAINT fk_82d029c59b6b5fba FOREIGN KEY (account_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
