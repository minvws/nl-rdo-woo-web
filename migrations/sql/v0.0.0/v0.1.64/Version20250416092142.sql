-- Migration Version20250416092142
-- Generated on 2025-04-22 05:33:32 by bin/console woopie:sql:dump
--

    ALTER TABLE department ADD landing_page_title VARCHAR(100) DEFAULT NULL;
    ALTER TABLE department ADD landing_page_description TEXT DEFAULT NULL;
    ALTER TABLE department ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP(0);
    ALTER TABLE department ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP(0);
    ALTER TABLE department ADD file_mimetype VARCHAR(100) DEFAULT NULL;
    ALTER TABLE department ADD file_path VARCHAR(1024) DEFAULT NULL;
    ALTER TABLE department ADD file_hash VARCHAR(128) DEFAULT NULL;
    ALTER TABLE department ADD file_size BIGINT NOT NULL DEFAULT 0;
    ALTER TABLE department ADD file_type VARCHAR(255) DEFAULT NULL;
    ALTER TABLE department ADD file_name VARCHAR(1024) DEFAULT NULL;
    ALTER TABLE department ADD file_uploaded BOOLEAN NOT NULL DEFAULT FALSE;
    ALTER TABLE department ADD file_source_type VARCHAR(255) DEFAULT NULL;
    ALTER TABLE department ADD file_page_count INT DEFAULT NULL;
    ALTER TABLE department ADD file_paginatable BOOLEAN DEFAULT false NOT NULL;


