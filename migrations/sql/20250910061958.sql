-- Migration Version20250910061958
-- Generated on 2025-09-10 06:20:36 by bin/console woopie:sql:dump
--

ALTER TABLE dossier ADD advisory_bodies JSON DEFAULT NULL;
UPDATE dossier SET advisory_bodies='[]' WHERE type='request-for-advice';


