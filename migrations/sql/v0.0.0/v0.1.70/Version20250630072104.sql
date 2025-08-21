-- Migration Version20250630072104
-- Generated on 2025-07-02 07:18:21 by bin/console woopie:sql:dump
--

    ALTER TABLE department ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE;
    ALTER TABLE department ALTER created_at DROP DEFAULT;
    ALTER TABLE department ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE;
    ALTER TABLE department ALTER updated_at DROP DEFAULT;
    ALTER TABLE department ALTER file_size TYPE BIGINT;
    ALTER TABLE department ALTER file_size DROP DEFAULT;
    ALTER TABLE department ALTER file_uploaded TYPE BOOLEAN;
    ALTER TABLE department ALTER file_uploaded DROP DEFAULT;
    ALTER INDEX idx_830914d0a76ed395 RENAME TO IDX_17BDE61FA76ED395;


