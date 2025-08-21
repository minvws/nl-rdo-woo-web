-- Migration Version20231128105341
-- Generated on 2023-11-29 07:33:19 by bin/console woopie:sql:dump
--

CREATE TABLE history (id UUID NOT NULL, type VARCHAR(255) NOT NULL, identifier UUID NOT NULL, created_dt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, context_key VARCHAR(255) NOT NULL, context JSON NOT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_27BA704B8CDE5729772E836A ON history (type, identifier);
COMMENT ON COLUMN history.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN history.identifier IS '(DC2Type:uuid)';
COMMENT ON COLUMN history.created_dt IS '(DC2Type:datetime_immutable)';

GRANT SELECT,INSERT ON TABLE history TO woopie;
