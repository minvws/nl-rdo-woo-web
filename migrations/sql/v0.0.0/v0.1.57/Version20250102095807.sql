-- Migration Version20250102095807
-- Generated on 2025-01-08 07:16:43 by bin/console woopie:sql:dump
--

ALTER TABLE batch_download ADD file_count INT NOT NULL DEFAULT 0;
UPDATE batch_download SET file_count = json_array_length(documents);
ALTER TABLE batch_download DROP documents;


