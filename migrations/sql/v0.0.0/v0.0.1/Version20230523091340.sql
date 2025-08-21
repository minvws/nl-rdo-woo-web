-- Migration Version20230523091340
-- Generated on 2023-07-14 09:45:15 by bin/console woopie:sql:dump
--

ALTER TABLE woo_request ADD token VARCHAR(255) NOT NULL DEFAULT '';


