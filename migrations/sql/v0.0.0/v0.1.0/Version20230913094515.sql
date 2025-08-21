-- Migration Version20230913094515
-- Generated on 2023-09-19 07:51:32 by bin/console woopie:sql:dump
--

CREATE TABLE audit_entry (id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, request JSON NOT NULL, event_code VARCHAR(255) NOT NULL, action_code VARCHAR(255) NOT NULL, failed BOOLEAN NOT NULL, PRIMARY KEY(id));
COMMENT ON COLUMN audit_entry.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN audit_entry.created_at IS '(DC2Type:datetime_immutable)';
CREATE TABLE encrypted_audit_entry (id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, data TEXT NOT NULL, PRIMARY KEY(id));
COMMENT ON COLUMN encrypted_audit_entry.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN encrypted_audit_entry.created_at IS '(DC2Type:datetime_immutable)';


