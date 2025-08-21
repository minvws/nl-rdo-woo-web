-- Migration Version20230513113150
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

DROP SEQUENCE user_id_seq CASCADE;
CREATE TABLE "user" (id UUID NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, mfa_token text DEFAULT NULL, mfa_recovery text DEFAULT NULL, enabled BOOLEAN NOT NULL, PRIMARY KEY(id));
ALTER TABLE "user" OWNER TO woo_dba;
GRANT SELECT,INSERT,UPDATE ON TABLE "user" TO woopie;

CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email);
COMMENT ON COLUMN "user".id IS '(DC2Type:uuid)';


