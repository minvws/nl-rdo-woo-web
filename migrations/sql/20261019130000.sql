-- Migration Version20261019130000
-- Generated on 2026-10-19 11:04:40 by bin/console woopie:sql:dump
--

ALTER TABLE upload ADD error_reason VARCHAR(50) DEFAULT NULL;


