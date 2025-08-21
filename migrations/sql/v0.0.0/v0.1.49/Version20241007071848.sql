-- Migration Version20241007071848
-- Generated on 2024-10-15 07:11:51 by bin/console woopie:sql:dump
--

INSERT INTO main_document(
                            id,
                            dossier_id,
                            formal_date,
                            type,
                            internal_reference,
                            language,
                            grounds,
                            created_at,
                            updated_at,
                            file_mimetype,
                            file_path,
                            file_size,
                            file_type,
                            file_source_type,
                            file_name,
                            file_uploaded,
                            file_page_count,
                            file_paginatable,
                            entity_type,
                            file_hash
                       ) SELECT
                             d.id,
                             d.dossier_id,
                             COALESCE(dos.publication_date, d.created_at),
                             'c_4f50ca9c',
                             '',
                             'Dutch',
                             '[]',
                             d.created_at,
                             d.updated_at,
                             d.file_mimetype,
                             d.file_path,
                             d.file_size,
                             d.file_type,
                             d.file_source_type,
                             d.file_name,
                             d.file_uploaded,
                             d.file_page_count,
                             d.file_paginatable,
                             'woo_decision_main_document',
                             d.file_hash
                       FROM decision_document d
                       JOIN dossier dos ON dos.id = d.dossier_id;
ALTER TABLE decision_document DROP CONSTRAINT fk_55b54548611c0c56;
DROP TABLE decision_document;


