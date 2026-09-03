<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260819142737 extends AbstractMigration
{
    public function preUp(Schema $schema): void
    {
        $count = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM document WHERE document_id IS NULL');

        $this->abortIf(
            $count > 0,
            sprintf('Cannot make document.document_id non-nullable: %d document row(s) still have a NULL document_id.', $count),
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document ALTER document_id SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document ALTER document_id DROP NOT NULL');
    }
}
