-- Migration Version20260821074045
-- Generated on 2026-08-22 11:56:12 by bin/console woopie:sql:dump
--

ALTER TABLE organisation ADD prefix VARCHAR(30) DEFAULT NULL;
CREATE UNIQUE INDEX uniq_e6e132b493b1868e ON organisation (prefix);


