-- Migration Version20230517121927
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE document ADD uploaded BOOLEAN NOT NULL DEFAULT FALSE;

