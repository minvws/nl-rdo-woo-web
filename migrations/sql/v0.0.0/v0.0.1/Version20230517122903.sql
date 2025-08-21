-- Migration Version20230517122903
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

CREATE TABLE department (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id));
ALTER TABLE department OWNER TO woo_dba;
GRANT SELECT,INSERT,UPDATE ON TABLE department TO woopie;
COMMENT ON COLUMN department.id IS '(DC2Type:uuid)';

CREATE TABLE department_head (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id));
ALTER TABLE department_head OWNER TO woo_dba;
GRANT SELECT,INSERT,UPDATE ON TABLE department_head TO woopie;
COMMENT ON COLUMN department_head.id IS '(DC2Type:uuid)';


