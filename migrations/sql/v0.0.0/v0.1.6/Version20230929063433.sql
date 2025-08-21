-- Migration Version20230929063433
-- Generated on 2023-10-02 09:24:18 by bin/console woopie:sql:dump
--

ALTER TABLE "user" ADD organisation_id UUID;
COMMENT ON COLUMN "user".organisation_id IS '(DC2Type:uuid)';
ALTER TABLE "user" ADD CONSTRAINT FK_8D93D6499E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
CREATE INDEX IDX_8D93D6499E6B1585 ON "user" (organisation_id);


