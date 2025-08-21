-- Migration Version20230522131824
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE document DROP department;
ALTER TABLE document DROP official;
ALTER TABLE document DROP subject;
ALTER TABLE document ALTER family_id DROP NOT NULL;
ALTER TABLE document ALTER document_id DROP NOT NULL;
ALTER TABLE document ALTER thread_id DROP NOT NULL;
ALTER TABLE document ALTER judgement DROP NOT NULL;
ALTER TABLE document ALTER grounds DROP DEFAULT;
ALTER TABLE document ALTER subjects DROP DEFAULT;
ALTER TABLE document ALTER grounds TYPE JSON USING grounds::JSON;
ALTER TABLE document ALTER subjects TYPE JSON USING subjects::JSON;
ALTER TABLE document ALTER period DROP NOT NULL;
COMMENT ON COLUMN document.grounds IS NULL;
COMMENT ON COLUMN document.subjects IS NULL;


