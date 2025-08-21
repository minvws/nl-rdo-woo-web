-- Migration Version20230511132918
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1;
ALTER SEQUENCE "user_id_seq" OWNER TO woo_dba;
GRANT ALL ON SEQUENCE "user_id_seq" TO woopie;

ALTER TABLE document ALTER department DROP DEFAULT;
ALTER TABLE document ALTER official DROP DEFAULT;
ALTER TABLE document ALTER document_type DROP DEFAULT;
ALTER TABLE document ALTER subject DROP DEFAULT;
ALTER TABLE document ALTER publication_type DROP DEFAULT;
ALTER TABLE document ALTER publication_section DROP DEFAULT;
ALTER TABLE document ALTER document_number DROP DEFAULT;
ALTER TABLE document ALTER document_date DROP DEFAULT;
ALTER TABLE document ALTER filename DROP DEFAULT;


