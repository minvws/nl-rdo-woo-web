-- Migration Version20250703093251
-- Generated on 2025-07-15 12:18:33 by bin/console woopie:sql:dump
--

    ALTER TABLE content_page ALTER slug TYPE VARCHAR(50);
    ALTER TABLE content_page ALTER title TYPE VARCHAR(200);
