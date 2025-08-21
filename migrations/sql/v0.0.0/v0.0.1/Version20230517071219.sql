-- Migration Version20230517071219
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE dossier DROP CONSTRAINT fk_3d48e03767ee6561;
DROP INDEX idx_3d48e03767ee6561;
ALTER TABLE dossier DROP woo_request_id;
ALTER TABLE "user" ALTER mfa_token TYPE TEXT;
ALTER TABLE "user" ALTER mfa_recovery TYPE TEXT;
ALTER TABLE "user" ALTER created_at DROP DEFAULT;
ALTER TABLE "user" ALTER updated_at DROP DEFAULT;


