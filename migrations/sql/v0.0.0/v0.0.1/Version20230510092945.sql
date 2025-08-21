-- Migration Version20230510092945
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE document ADD filename VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE document DROP status;


