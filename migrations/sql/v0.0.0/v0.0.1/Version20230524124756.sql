-- Migration Version20230524124756
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE "user" ADD changepwd BOOLEAN NOT NULL DEFAULT FALSE;


