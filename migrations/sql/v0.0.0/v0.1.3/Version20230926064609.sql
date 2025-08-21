-- Migration Version20230925080952
-- Generated on 2023-09-26 21:38:30 by Herman
--

ALTER TABLE dossier ADD completed BOOLEAN NOT NULL DEFAULT TRUE;
