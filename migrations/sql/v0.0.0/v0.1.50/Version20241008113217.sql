-- Migration Version20241008113217
-- Generated on 2024-10-15 07:11:51 by bin/console woopie:sql:dump
--

ALTER TABLE document_dossier DROP CONSTRAINT fk_992746eb611c0c56;
DROP INDEX idx_992746eb611c0c56;
ALTER TABLE document_dossier DROP CONSTRAINT document_dossier_pkey;
ALTER TABLE document_dossier RENAME COLUMN dossier_id TO woo_decision_id;
ALTER TABLE document_dossier ADD CONSTRAINT FK_992746EB21C2E34D FOREIGN KEY (woo_decision_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
CREATE INDEX IDX_992746EB21C2E34D ON document_dossier (woo_decision_id);
ALTER TABLE document_dossier ADD PRIMARY KEY (document_id, woo_decision_id);
ALTER TABLE inquiry_dossier DROP CONSTRAINT fk_d6558e92611c0c56;
DROP INDEX idx_d6558e92611c0c56;
ALTER TABLE inquiry_dossier DROP CONSTRAINT inquiry_dossier_pkey;
ALTER TABLE inquiry_dossier RENAME COLUMN dossier_id TO woo_decision_id;
ALTER TABLE inquiry_dossier ADD CONSTRAINT FK_D6558E9221C2E34D FOREIGN KEY (woo_decision_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
CREATE INDEX IDX_D6558E9221C2E34D ON inquiry_dossier (woo_decision_id);
ALTER TABLE inquiry_dossier ADD PRIMARY KEY (inquiry_id, woo_decision_id);


