-- Migration Version20240409003630
-- Generated on 2024-04-09 20:43:09 by bin/console woopie:sql:dump
--

ALTER TABLE decision_attachment ADD internal_reference VARCHAR(255) NOT NULL;


