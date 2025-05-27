<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250416092142 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE department ADD landing_page_title VARCHAR(100) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department ADD landing_page_description TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP(0)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP(0)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department ADD file_mimetype VARCHAR(100) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department ADD file_path VARCHAR(1024) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department ADD file_hash VARCHAR(128) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department ADD file_size BIGINT NOT NULL DEFAULT 0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department ADD file_type VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department ADD file_name VARCHAR(1024) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department ADD file_uploaded BOOLEAN NOT NULL DEFAULT FALSE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department ADD file_source_type VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department ADD file_page_count INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department ADD file_paginatable BOOLEAN DEFAULT false NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE department DROP landing_page_title
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department DROP landing_page_description
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department DROP created_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department DROP updated_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department DROP file_mimetype
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department DROP file_path
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department DROP file_hash
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department DROP file_size
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department DROP file_type
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department DROP file_name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department DROP file_uploaded
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department DROP file_source_type
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department DROP file_page_count
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE department DROP file_paginatable
        SQL);
    }
}
