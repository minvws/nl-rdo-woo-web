-- Migration Version20230928062848
-- Generated on 2023-10-02 09:24:18 by bin/console woopie:sql:dump
--

CREATE TABLE organisation (id UUID NOT NULL, name VARCHAR(255) NOT NULL, department VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id));
CREATE UNIQUE INDEX UNIQ_E6E132B45E237E06 ON organisation (name);
COMMENT ON COLUMN organisation.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN organisation.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN organisation.updated_at IS '(DC2Type:datetime_immutable)';


