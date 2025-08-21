-- Migration Version20240731051048
-- Generated on 2024-07-31 11:48:38 by bin/console woopie:sql:dump
--

ALTER TABLE department ADD slug VARCHAR(20);
ALTER TABLE department ADD public BOOLEAN NOT NULL DEFAULT TRUE;
DROP INDEX IF EXISTS department_pk;
DROP INDEX IF EXISTS UNIQ_CD1DE18A5E237E06;
CREATE UNIQUE INDEX UNIQ_CD1DE18A74C9F71C ON department (short_tag);
CREATE UNIQUE INDEX UNIQ_CD1DE18A989D9B62 ON department (slug);
CREATE UNIQUE INDEX UNIQ_CD1DE18A5E237E06 ON department (name);
UPDATE department SET slug = lower(regexp_replace(short_tag, '[^\w]+',''));
ALTER TABLE department ALTER slug SET NOT NULL;
ALTER TABLE department ALTER short_tag SET NOT NULL;
ALTER TABLE department ALTER public DROP DEFAULT;


