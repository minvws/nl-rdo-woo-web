-- Migration Version20231016074350
-- Generated on 2023-10-16 09:45:48 by bin/console woopie:sql:dump
--

ALTER TABLE document_prefix ALTER description TYPE VARCHAR(1024);
ALTER TABLE ingest_log ALTER message TYPE VARCHAR(1024);


