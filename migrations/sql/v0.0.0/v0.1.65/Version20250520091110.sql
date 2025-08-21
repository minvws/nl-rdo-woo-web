-- Migration Version20250520091110
-- Generated on 2025-05-20 10:38:28 by bin/console woopie:sql:dump
--


            UPDATE main_document
            SET formal_date = d.decision_date
            FROM dossier d
            WHERE d.id = main_document.dossier_id
                AND main_document.formal_date != d.decision_date
                AND d.type = 'woo-decision'
        ;


