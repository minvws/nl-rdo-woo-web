-- Migration Version20250703093251
-- Generated on 2025-07-09 09:07:00 by bin/console woopie:sql:dump
--

    ALTER TABLE content_page ALTER slug TYPE VARCHAR(50);
    ALTER TABLE content_page ALTER title TYPE VARCHAR(200);


