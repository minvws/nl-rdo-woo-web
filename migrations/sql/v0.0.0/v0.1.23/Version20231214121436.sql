-- Migration Version20231214121436
-- Generated on 2023-12-14 12:26:00 by bin/console woopie:sql:dump
--

UPDATE dossier SET default_subjects = replace(default_subjects::text, '"Vaccinaties & medicatie"', '"Vaccinaties en medicatie"')::jsonb;;
UPDATE document SET subjects = replace(subjects::text, '"Vaccinaties & medicatie"', '"Vaccinaties en medicatie"')::jsonb;;


