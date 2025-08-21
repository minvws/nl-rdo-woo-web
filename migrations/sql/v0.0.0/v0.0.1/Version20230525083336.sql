-- Migration Version20230525083336
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE dossier_government_official DROP CONSTRAINT fk_4adf1a7d49b3897d;
DROP INDEX idx_4adf1a7d49b3897d;
ALTER TABLE dossier_government_official RENAME COLUMN department_head_id TO government_official_id;
ALTER TABLE dossier_government_official ADD CONSTRAINT FK_C79596A154DE3212 FOREIGN KEY (government_official_id) REFERENCES government_official (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
CREATE INDEX IDX_C79596A154DE3212 ON dossier_government_official (government_official_id);
ALTER INDEX idx_4adf1a7d611c0c56 RENAME TO IDX_C79596A1611C0C56;
ALTER INDEX idx_5596e8b667ee6561 RENAME TO IDX_7E3EC07FA7AD6D71;
ALTER INDEX idx_5596e8b6c33f7837 RENAME TO IDX_7E3EC07FC33F7837;


