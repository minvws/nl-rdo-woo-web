-- Migration Version20260822055205
-- Generated on 2026-08-22 11:56:12 by bin/console woopie:sql:dump
--

    UPDATE organisation AS o
    SET prefix = (
        SELECT dp.prefix
        FROM document_prefix AS dp
        WHERE dp.organisation_id = o.id
          AND dp.archived = false
        ORDER BY dp.prefix ASC
        LIMIT 1
    )
    WHERE o.prefix IS NULL;
