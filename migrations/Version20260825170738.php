<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260825170738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow document numbers longer than 255 characters';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document ALTER COLUMN document_number TYPE TEXT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document ALTER COLUMN document_number TYPE VARCHAR(255)');
    }
}
