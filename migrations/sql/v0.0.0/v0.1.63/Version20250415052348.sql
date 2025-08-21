-- Migration Version20250415052348
-- Generated on 2025-04-15 11:34:42 by bin/console woopie:sql:dump
--

    CREATE UNIQUE INDEX lower_case_document_nr ON document ((lower(document_nr)));
    DROP INDEX UNIQ_D8698A7678AA5BA1;


