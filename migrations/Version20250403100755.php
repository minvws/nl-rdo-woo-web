<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250403100755 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE upload (id UUID NOT NULL, upload_id VARCHAR(50) NOT NULL, external_id VARCHAR(255) DEFAULT NULL, upload_group_id VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, size BIGINT DEFAULT NULL, mimetype VARCHAR(100) DEFAULT NULL, filename VARCHAR(255) DEFAULT NULL, error JSON DEFAULT NULL, context JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_830914D0A76ED395 ON upload (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE upload ADD CONSTRAINT FK_830914D0A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE upload DROP CONSTRAINT FK_830914D0A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE upload
        SQL);
    }
}
