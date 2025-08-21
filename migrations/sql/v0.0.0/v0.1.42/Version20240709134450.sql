-- Migration Version20240709134450
-- Generated on 2024-07-12 06:59:05 by bin/console woopie:sql:dump
--
CREATE TABLE organisation_department (organisation_id UUID NOT NULL, department_id UUID NOT NULL, PRIMARY KEY(organisation_id, department_id));
CREATE INDEX IDX_45F0F7B89E6B1585 ON organisation_department (organisation_id);
CREATE INDEX IDX_45F0F7B8AE80F5DF ON organisation_department (department_id);
COMMENT ON COLUMN organisation_department.organisation_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN organisation_department.department_id IS '(DC2Type:uuid)';
ALTER TABLE organisation_department ADD CONSTRAINT FK_45F0F7B89E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE organisation_department ADD CONSTRAINT FK_45F0F7B8AE80F5DF FOREIGN KEY (department_id) REFERENCES department (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE organisation DROP CONSTRAINT fk_e6e132b4ae80f5df;
DROP INDEX idx_e6e132b4ae80f5df;
INSERT INTO organisation_department(organisation_id, department_id) SELECT id, department_id FROM organisation;
ALTER TABLE organisation DROP department_id;

GRANT SELECT,INSERT,UPDATE,DELETE ON TABLE organisation_department TO woopie;
