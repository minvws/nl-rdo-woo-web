-- Migration Version20230522071250
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

CREATE TABLE dossier_department (dossier_id UUID NOT NULL, department_id UUID NOT NULL, PRIMARY KEY(dossier_id, department_id));
ALTER TABLE dossier_department OWNER TO woo_dba;

GRANT SELECT,INSERT,UPDATE ON TABLE dossier_department TO woopie;
CREATE INDEX IDX_E367A5AF611C0C56 ON dossier_department (dossier_id);
CREATE INDEX IDX_E367A5AFAE80F5DF ON dossier_department (department_id);
COMMENT ON COLUMN dossier_department.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN dossier_department.department_id IS '(DC2Type:uuid)';

CREATE TABLE dossier_department_head (dossier_id UUID NOT NULL, department_head_id UUID NOT NULL, PRIMARY KEY(dossier_id, department_head_id));
ALTER TABLE dossier_department_head OWNER TO woo_dba;
GRANT SELECT,INSERT,UPDATE ON TABLE dossier_department_head TO woopie;

CREATE INDEX IDX_4ADF1A7D611C0C56 ON dossier_department_head (dossier_id);
CREATE INDEX IDX_4ADF1A7D49B3897D ON dossier_department_head (department_head_id);
COMMENT ON COLUMN dossier_department_head.dossier_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN dossier_department_head.department_head_id IS '(DC2Type:uuid)';
ALTER TABLE dossier_department ADD CONSTRAINT FK_E367A5AF611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE dossier_department ADD CONSTRAINT FK_E367A5AFAE80F5DF FOREIGN KEY (department_id) REFERENCES department (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE dossier_department_head ADD CONSTRAINT FK_4ADF1A7D611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE dossier_department_head ADD CONSTRAINT FK_4ADF1A7D49B3897D FOREIGN KEY (department_head_id) REFERENCES department_head (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;


