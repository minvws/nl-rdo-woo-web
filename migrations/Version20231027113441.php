<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231027113441 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update withdraw reason value to new key in enum';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE document SET withdraw_reason='incorrect_attachment' WHERE withdraw_reason='incorrect_document'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE document SET withdraw_reason='incorrect_document' WHERE withdraw_reason='incorrect_attachment'");
    }
}
