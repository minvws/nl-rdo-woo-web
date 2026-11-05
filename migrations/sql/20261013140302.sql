-- Migration Version20261013140302
-- Generated on 2026-10-14 10:06:45 by bin/console woopie:sql:dump
--

CREATE INDEX IDX_795FD9BB611C0C5643625D9FBF396750 ON attachment (dossier_id, updated_at, id);
CREATE INDEX IDX_F3F4EC1024CD504D ON batch_download (expiration);
CREATE INDEX IDX_D8698A76C35E566A ON document (family_id);
CREATE INDEX IDX_D8698A76C33F7837 ON document (document_id);
CREATE INDEX IDX_D8698A76E2904019 ON document (thread_id);
CREATE INDEX IDX_3D48E0379E6B15857B00651C8B8E8428 ON dossier (organisation_id, status, created_at);
CREATE INDEX IDX_3D48E0379E6B1585ED18940B ON dossier (organisation_id, decision_date);
CREATE INDEX IDX_3D48E0379E6B15853AF85C6E ON dossier (organisation_id, completed);
CREATE INDEX IDX_3D48E0377B00651CA166B6B7 ON dossier (status, publication_date);
CREATE INDEX IDX_3D48E0377B00651C43625D9FBF396750 ON dossier (status, updated_at, id);
DROP INDEX idx_27ba704b8cde5729772e836a;
CREATE INDEX IDX_27BA704B8CDE5729772E836AF6F9706DBF396750 ON history (type, identifier, created_dt, id);
CREATE INDEX IDX_5A3903F09E6B158585C88591 ON inquiry (organisation_id, inquiry_number);
CREATE INDEX IDX_5A3903F09E6B158543625D9F ON inquiry (organisation_id, updated_at);
CREATE INDEX IDX_D8900FF77B00651CD46F4E3 ON production_report_process_run (status, started_at);
CREATE INDEX IDX_FBCE3E7AB751E0AD5E237E06 ON subject (landing_page_status, name);
CREATE INDEX IDX_FBCE3E7A9E6B15855E237E06 ON subject (organisation_id, name);
CREATE INDEX IDX_17BDE61FCCCFBA31 ON upload (upload_id);
CREATE INDEX IDX_17BDE61F7B00651C ON upload (status);
CREATE INDEX IDX_17BDE61F43625D9F ON upload (updated_at);
CREATE INDEX IDX_9BCD33177B00651C8B8E8428 ON woo_index_sitemap (status, created_at);


