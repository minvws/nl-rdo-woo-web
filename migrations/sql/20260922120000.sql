-- Migration Version20260922120000
-- Generated on 2026-09-22 09:34:34 by bin/console woopie:sql:dump
--

ALTER TABLE production_report_process_run ADD has_matter BOOLEAN DEFAULT false NOT NULL;


