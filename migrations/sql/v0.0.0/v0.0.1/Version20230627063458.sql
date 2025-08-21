-- Migration Version20230627063458
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE document ADD suspended BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE document ADD withdrawn BOOLEAN NOT NULL DEFAULT FALSE;


