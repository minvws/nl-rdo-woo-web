-- Migration Version20231129123838
-- Generated on 2023-12-04 12:48:53 by bin/console woopie:sql:dump
--

alter table "user" alter column roles type jsonb using roles::jsonb;
