<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241230095031 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE attachment ADD withdrawn BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE attachment ADD withdraw_reason VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE attachment ADD withdraw_explanation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE attachment ADD withdraw_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN attachment.withdraw_date IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE attachment DROP withdrawn');
        $this->addSql('ALTER TABLE attachment DROP withdraw_reason');
        $this->addSql('ALTER TABLE attachment DROP withdraw_explanation');
        $this->addSql('ALTER TABLE attachment DROP withdraw_date');
    }
}
