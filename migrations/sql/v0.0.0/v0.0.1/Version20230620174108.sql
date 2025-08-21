-- Migration Version20230620174108
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE slurper_progress DROP CONSTRAINT fk_b407f9fdc33f7837;
DROP TABLE slurper_progress;
DROP INDEX uniq_d8698a768047ea50;
ALTER TABLE document DROP slurper_progress_id;
ALTER TABLE document ALTER family_id DROP DEFAULT;
ALTER TABLE document ALTER document_id DROP DEFAULT;
ALTER TABLE document ALTER thread_id DROP DEFAULT;
ALTER TABLE document ALTER judgement DROP DEFAULT;
ALTER TABLE document ALTER period DROP DEFAULT;
ALTER TABLE document ALTER uploaded DROP DEFAULT;
ALTER TABLE document ALTER source_type DROP DEFAULT;
ALTER TABLE document ALTER class DROP DEFAULT;
ALTER TABLE document_prefix ALTER description DROP DEFAULT;
ALTER TABLE dossier ALTER publication_reason DROP DEFAULT;
ALTER TABLE dossier ALTER decision DROP DEFAULT;


