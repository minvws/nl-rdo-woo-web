-- Migration Version20230928093756
-- Generated on 2023-10-02 09:24:18 by bin/console woopie:sql:dump
--

ALTER TABLE organisation ADD department_id UUID NOT NULL;
ALTER TABLE organisation DROP department;
COMMENT ON COLUMN organisation.department_id IS '(DC2Type:uuid)';
ALTER TABLE organisation ADD CONSTRAINT FK_E6E132B4AE80F5DF FOREIGN KEY (department_id) REFERENCES department (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
CREATE INDEX IDX_E6E132B4AE80F5DF ON organisation (department_id);


