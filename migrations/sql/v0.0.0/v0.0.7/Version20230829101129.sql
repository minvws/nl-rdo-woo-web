-- Migration Version20230829101129
-- Generated on 2023-08-29 11:37:06 by bin/console woopie:sql:dump
--

ALTER TABLE inquiry_dossier DROP CONSTRAINT inquiry_dossier_pkey;
ALTER TABLE inquiry_dossier ADD PRIMARY KEY (inquiry_id, dossier_id);


