-- Migration Version20230929070108
-- Generated on 2023-10-02 09:24:18 by bin/console woopie:sql:dump
--

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
INSERT INTO department (id, name, short_tag) SELECT uuid_generate_v4(), 'Ministerie van Volksgezondheid, Welzijn en Sport', 'VWS' WHERE NOT EXISTS (SELECT 1 FROM department WHERE name='Ministerie van Volksgezondheid, Welzijn en Sport');
INSERT INTO organisation (id, name, created_at, updated_at, department_id) VALUES (uuid_generate_v4(), 'Programmadirectie Openbaarheid', now(), now(), (SELECT id as department_id FROM department WHERE name='Ministerie van Volksgezondheid, Welzijn en Sport'));
UPDATE "user" SET organisation_id=(SELECT id as organisation_id FROM organisation WHERE name='Programmadirectie Openbaarheid') WHERE organisation_id IS NULL;
ALTER TABLE "user" ALTER organisation_id SET NOT NULL;


