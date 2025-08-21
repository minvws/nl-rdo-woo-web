-- Migration Version20230627084827
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE batch_download ADD status VARCHAR(255) NOT NULL DEFAULT '';


