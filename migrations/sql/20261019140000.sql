-- Migration Version20261019140000
-- Generated on 2026-10-19 14:00:00 by bin/console woopie:sql:dump
--

ALTER TABLE main_document ADD spreadsheet_table JSON DEFAULT NULL;
ALTER TABLE main_document ADD spreadsheet_table_truncated BOOLEAN DEFAULT false NOT NULL;

