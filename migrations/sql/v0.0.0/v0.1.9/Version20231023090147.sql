-- Migration Version20231023090147
-- Generated on 2023-10-23 11:17:00 by bin/console woopie:sql:dump
--

CREATE TABLE login_activity (id UUID NOT NULL, account_id UUID NOT NULL, login_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_82D029C59B6B5FBA ON login_activity (account_id);
COMMENT ON COLUMN login_activity.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN login_activity.account_id IS '(DC2Type:uuid)';
COMMENT ON COLUMN login_activity.login_at IS '(DC2Type:datetime_immutable)';
ALTER TABLE login_activity ADD CONSTRAINT FK_82D029C59B6B5FBA FOREIGN KEY (account_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE login_activity OWNER TO woo_dba;
GRANT SELECT,INSERT ON TABLE login_activity TO woopie;
