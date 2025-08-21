-- Migration Version20231122093513
-- Generated on 2023-11-22 11:14:58 by bin/console woopie:sql:dump
--

UPDATE dossier SET default_subjects = replace(default_subjects::text, '"Opstarten"', '"Opstart Corona"')::jsonb;;
UPDATE dossier SET default_subjects = replace(default_subjects::text, '"Overleggen VWS"', '"Overleg VWS"')::jsonb;;
UPDATE dossier SET default_subjects = replace(default_subjects::text, '"Vaccinaties"', '"Vaccinaties & medicatie"')::jsonb;;
UPDATE document SET subjects = replace(subjects::text, '"Opstarten"', '"Opstart Corona"')::jsonb;;
UPDATE document SET subjects = replace(subjects::text, '"Overleggen VWS"', '"Overleg VWS"')::jsonb;;
UPDATE document SET subjects = replace(subjects::text, '"Vaccinaties"', '"Vaccinaties & medicatie"')::jsonb;;


