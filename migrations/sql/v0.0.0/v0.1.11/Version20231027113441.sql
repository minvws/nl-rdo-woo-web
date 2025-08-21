-- Migration Version20231027113441
-- Generated on 2023-10-27 09:42:28 by bin/console woopie:sql:dump
--

UPDATE document SET withdraw_reason='incorrect_attachment' WHERE withdraw_reason='incorrect_document';


