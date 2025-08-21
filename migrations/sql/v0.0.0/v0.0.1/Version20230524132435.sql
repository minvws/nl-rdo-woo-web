-- Migration Version20230524132435
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE "user" ALTER changepwd DROP DEFAULT;
ALTER TABLE woo_request ALTER token DROP DEFAULT;
ALTER TABLE department_head RENAME TO government_official;
ALTER TABLE dossier_department_head RENAME TO dossier_government_official;


