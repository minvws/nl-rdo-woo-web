-- Migration Version20250722134706
-- Generated on 2025-07-24 09:43:00 by bin/console woopie:sql:dump
--

ALTER TABLE document DROP summary;
ALTER TABLE document DROP title;
ALTER TABLE document ALTER remark TYPE VARCHAR(1000);
ALTER TABLE document ALTER withdraw_explanation TYPE VARCHAR(1000);
ALTER TABLE document ALTER document_id TYPE VARCHAR(170);


