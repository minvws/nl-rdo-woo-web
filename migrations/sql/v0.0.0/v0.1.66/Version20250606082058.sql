-- Migration Version20250606082058
-- Generated on 2025-06-11 08:24:18 by bin/console woopie:sql:dump
--

    CREATE TABLE content_page (
        slug VARCHAR(20) NOT NULL,
        title VARCHAR(100) NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
        updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
        PRIMARY KEY(slug)
  );


